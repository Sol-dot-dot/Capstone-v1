import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class MyBooksPage extends StatefulWidget {
  final String studentId;

  const MyBooksPage({Key? key, required this.studentId}) : super(key: key);

  @override
  _MyBooksPageState createState() => _MyBooksPageState();
}

class _MyBooksPageState extends State<MyBooksPage> {
  List<Map<String, dynamic>> _borrowedBooks = [];
  List<Map<String, dynamic>> _fines = [];
  bool _isLoading = false;
  double _totalFines = 0.0;

  @override
  void initState() {
    super.initState();
    _loadBorrowedBooks();
    _loadFines();
  }

  Future<void> _loadBorrowedBooks() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.get(
        Uri.parse('http://10.0.2.2:8080/api/api_borrowing.php?action=get_borrowed_books&student_id=${widget.studentId}'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _borrowedBooks = List<Map<String, dynamic>>.from(data['borrowed_books']);
          });
        }
      }
    } catch (e) {
      _showMessage('Error loading borrowed books: $e', isError: true);
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadFines() async {
    try {
      final response = await http.get(
        Uri.parse('http://10.0.2.2:8080/api/api_borrowing.php?action=get_fines&student_id=${widget.studentId}'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _fines = List<Map<String, dynamic>>.from(data['fines'] ?? []);
            // Calculate total fines from the fines list
            _totalFines = _fines.fold(0.0, (sum, fine) => sum + (fine['fine_amount']?.toDouble() ?? 0.0));
          });
        }
      }
    } catch (e) {
      print('Error loading fines: $e');
      // Don't show error message for fines loading to avoid UI disruption
      setState(() {
        _fines = [];
        _totalFines = 0.0;
      });
    }
  }

  Future<void> _returnBook(int borrowingId) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: Color(0xFF16213E),
        title: Text('Return Book', style: TextStyle(color: Colors.white)),
        content: Text(
          'Are you sure you want to return this book?',
          style: TextStyle(color: Colors.white70),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text('Cancel', style: TextStyle(color: Colors.white70)),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text('Return', style: TextStyle(color: Colors.blue)),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.post(
        Uri.parse('http://10.0.2.2:8080/api/api_borrowing.php'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {
          'action': 'return_book',
          'borrowing_id': borrowingId.toString(),
          'student_id': widget.studentId,
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _showMessage('Book returned successfully!');
          if (data['fine_amount'] > 0) {
            _showMessage('Fine applied: \$${data['fine_amount'].toStringAsFixed(2)}', isError: true);
          }
          _loadBorrowedBooks();
          _loadFines();
        } else {
          _showMessage(data['error'], isError: true);
        }
      }
    } catch (e) {
      _showMessage('Network error: $e', isError: true);
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _showMessage(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
        duration: Duration(seconds: 3),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Color(0xFF1A1A2E),
      appBar: AppBar(
        title: Text('My Books', style: TextStyle(color: Colors.white)),
        backgroundColor: Color(0xFF16213E),
        iconTheme: IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: Colors.blue))
          : RefreshIndicator(
              onRefresh: () async {
                await _loadBorrowedBooks();
                await _loadFines();
              },
              child: SingleChildScrollView(
                physics: AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Fines Summary Card
                    if (_totalFines > 0) _buildFinesSummaryCard(),
                    
                    // Currently Borrowed Books
                    _buildBorrowedBooksSection(),
                    
                    SizedBox(height: 20),
                    
                    // Fines History
                    if (_fines.isNotEmpty) _buildFinesHistorySection(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildFinesSummaryCard() {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(16),
      margin: EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: Color(0xFF16213E),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.red.withOpacity(0.5)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.warning, color: Colors.red),
              SizedBox(width: 8),
              Text(
                'Outstanding Fines',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          SizedBox(height: 8),
          Text(
            '\$${_totalFines.toStringAsFixed(2)}',
            style: TextStyle(
              color: Colors.red,
              fontSize: 24,
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            'Please pay your fines to continue borrowing',
            style: TextStyle(color: Colors.white70, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _buildBorrowedBooksSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Currently Borrowed (${_borrowedBooks.length})',
          style: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        SizedBox(height: 12),
        if (_borrowedBooks.isEmpty)
          Container(
            width: double.infinity,
            padding: EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: Color(0xFF16213E),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                Icon(Icons.book_outlined, color: Colors.white54, size: 48),
                SizedBox(height: 12),
                Text(
                  'No books currently borrowed',
                  style: TextStyle(color: Colors.white70, fontSize: 16),
                ),
                Text(
                  'Visit the borrowing section to borrow books',
                  style: TextStyle(color: Colors.white54, fontSize: 12),
                ),
              ],
            ),
          )
        else
          ListView.separated(
            shrinkWrap: true,
            physics: NeverScrollableScrollPhysics(),
            itemCount: _borrowedBooks.length,
            separatorBuilder: (context, index) => SizedBox(height: 12),
            itemBuilder: (context, index) {
              final book = _borrowedBooks[index];
              return _buildBorrowedBookCard(book);
            },
          ),
      ],
    );
  }

  Widget _buildBorrowedBookCard(Map<String, dynamic> book) {
    final isOverdue = book['days_overdue'] > 0;
    final dueDate = DateTime.parse(book['due_date']);
    final fineAmount = book['fine_amount'] ?? 0.0;

    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Color(0xFF16213E),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isOverdue ? Colors.red.withOpacity(0.5) : Colors.transparent,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Book Cover Placeholder
              Container(
                width: 60,
                height: 80,
                decoration: BoxDecoration(
                  color: Color(0xFF0F1419),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: book['cover_image'] != null
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.network(
                          book['cover_image'],
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              Icon(Icons.book, color: Colors.white54),
                        ),
                      )
                    : Icon(Icons.book, color: Colors.white54),
              ),
              SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      book['title'] ?? 'Unknown Title',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    if (book['author_name'] != null)
                      Text(
                        'by ${book['author_name']}',
                        style: TextStyle(color: Colors.white70, fontSize: 12),
                      ),
                    SizedBox(height: 4),
                    Text(
                      'Code: ${book['book_code']}',
                      style: TextStyle(color: Colors.white54, fontSize: 11),
                    ),
                    SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(
                          Icons.schedule,
                          color: isOverdue ? Colors.red : Colors.blue,
                          size: 16,
                        ),
                        SizedBox(width: 4),
                        Text(
                          isOverdue
                              ? 'Overdue by ${book['days_overdue']} days'
                              : 'Due: ${dueDate.day}/${dueDate.month}/${dueDate.year}',
                          style: TextStyle(
                            color: isOverdue ? Colors.red : Colors.white70,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                    if (fineAmount > 0) ...[
                      SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.warning, color: Colors.red, size: 16),
                          SizedBox(width: 4),
                          Text(
                            'Fine: \$${fineAmount.toStringAsFixed(2)}',
                            style: TextStyle(color: Colors.red, fontSize: 12),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: EdgeInsets.symmetric(vertical: 12),
            decoration: BoxDecoration(
              color: Color(0xFF0F1419),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.white24),
            ),
            child: Text(
              'Return to Library Staff',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Colors.white54, 
                fontWeight: FontWeight.w500,
                fontSize: 13,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFinesHistorySection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Fines History',
          style: TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        SizedBox(height: 12),
        ListView.separated(
          shrinkWrap: true,
          physics: NeverScrollableScrollPhysics(),
          itemCount: _fines.length,
          separatorBuilder: (context, index) => SizedBox(height: 8),
          itemBuilder: (context, index) {
            final fine = _fines[index];
            final isPending = fine['status'] == 'pending';
            
            return Container(
              padding: EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Color(0xFF16213E),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: isPending ? Colors.red.withOpacity(0.3) : Colors.transparent,
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    isPending ? Icons.warning : Icons.check_circle,
                    color: isPending ? Colors.red : Colors.green,
                    size: 20,
                  ),
                  SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          fine['book_title'] ?? 'Unknown Book',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          '${fine['days_overdue']} days overdue',
                          style: TextStyle(color: Colors.white70, fontSize: 12),
                        ),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        '\$${fine['fine_amount'].toStringAsFixed(2)}',
                        style: TextStyle(
                          color: isPending ? Colors.red : Colors.green,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Text(
                        isPending ? 'Pending' : 'Paid',
                        style: TextStyle(
                          color: isPending ? Colors.red : Colors.green,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          },
        ),
      ],
    );
  }
}
