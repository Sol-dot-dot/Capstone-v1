import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class BorrowingPage extends StatefulWidget {
  final String studentId;

  const BorrowingPage({Key? key, required this.studentId}) : super(key: key);

  @override
  _BorrowingPageState createState() => _BorrowingPageState();
}

class _BorrowingPageState extends State<BorrowingPage> {
  final TextEditingController _bookCodeController = TextEditingController();
  List<Map<String, dynamic>> _scannedBooks = [];
  Map<String, dynamic>? _eligibility;
  bool _isLoading = false;
  String _message = '';

  @override
  void initState() {
    super.initState();
    _checkEligibility();
  }

  Future<void> _checkEligibility() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.get(
        Uri.parse('http://10.0.2.2:8080/api/api_borrowing.php?action=check_borrowing_eligibility&student_id=${widget.studentId}'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _eligibility = data['eligibility'];
          });
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

  Future<void> _scanBook() async {
    final bookCode = _bookCodeController.text.trim();
    if (bookCode.isEmpty) {
      _showMessage('Please enter a book code', isError: true);
      return;
    }

    if (_scannedBooks.length >= 3) {
      _showMessage('Maximum 3 books can be borrowed at once', isError: true);
      return;
    }

    // Check if book already scanned
    if (_scannedBooks.any((book) => book['book_code'] == bookCode)) {
      _showMessage('Book already scanned', isError: true);
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.get(
        Uri.parse('http://10.0.2.2:8080/api/api_borrowing.php?action=scan_book&book_code=$bookCode'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          final book = data['book'];
          if (book['is_available']) {
            setState(() {
              _scannedBooks.add(book);
              _bookCodeController.clear();
            });
            _showMessage('Book scanned successfully');
          } else {
            _showMessage('Book is not available for borrowing', isError: true);
          }
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

  Future<void> _borrowBooks() async {
    if (_scannedBooks.isEmpty) {
      _showMessage('Please scan at least one book', isError: true);
      return;
    }

    if (_eligibility != null && !_eligibility!['can_borrow']) {
      _showMessage('You are not eligible to borrow books', isError: true);
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final bookCodes = _scannedBooks.map((book) => book['book_code']).toList();
      
      final response = await http.post(
        Uri.parse('http://10.0.2.2:8080/api/api_borrowing.php'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: {
          'action': 'borrow_books',
          'student_id': widget.studentId,
          'book_codes': json.encode(bookCodes),
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _showMessage('Books borrowed successfully!');
          setState(() {
            _scannedBooks.clear();
          });
          _checkEligibility(); // Refresh eligibility
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

  void _removeBook(int index) {
    setState(() {
      _scannedBooks.removeAt(index);
    });
  }

  void _showMessage(String message, {bool isError = false}) {
    setState(() {
      _message = message;
    });
    
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
        title: Text('Borrow Books', style: TextStyle(color: Colors.white)),
        backgroundColor: Color(0xFF16213E),
        iconTheme: IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? Center(child: CircularProgressIndicator(color: Colors.blue))
          : SingleChildScrollView(
              padding: EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Eligibility Status Card
                  if (_eligibility != null) _buildEligibilityCard(),
                  
                  SizedBox(height: 20),
                  
                  // Book Scanning Section
                  _buildScanningSection(),
                  
                  SizedBox(height: 20),
                  
                  // Scanned Books List
                  if (_scannedBooks.isNotEmpty) _buildScannedBooksList(),
                  
                  SizedBox(height: 30),
                  
                  // Borrow Button
                  if (_scannedBooks.isNotEmpty) _buildBorrowButton(),
                ],
              ),
            ),
    );
  }

  Widget _buildEligibilityCard() {
    final canBorrow = _eligibility!['can_borrow'];
    final reasons = List<String>.from(_eligibility!['reasons'] ?? []);
    
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Color(0xFF16213E),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: canBorrow ? Colors.green : Colors.red),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(
                canBorrow ? Icons.check_circle : Icons.error,
                color: canBorrow ? Colors.green : Colors.red,
              ),
              SizedBox(width: 8),
              Text(
                canBorrow ? 'Eligible to Borrow' : 'Not Eligible',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          SizedBox(height: 12),
          Text(
            'Current Books: ${_eligibility!['current_books']}/${_eligibility!['max_concurrent']}',
            style: TextStyle(color: Colors.white70),
          ),
          Text(
            'Semester Total: ${_eligibility!['semester_total']}/${_eligibility!['max_semester']}',
            style: TextStyle(color: Colors.white70),
          ),
          if (_eligibility!['pending_fines'] > 0)
            Text(
              'Pending Fines: \$${_eligibility!['pending_fines'].toStringAsFixed(2)}',
              style: TextStyle(color: Colors.red),
            ),
          if (reasons.isNotEmpty) ...[
            SizedBox(height: 8),
            ...reasons.map((reason) => Text(
              '• $reason',
              style: TextStyle(color: Colors.red, fontSize: 12),
            )),
          ],
        ],
      ),
    );
  }

  Widget _buildScanningSection() {
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Color(0xFF16213E),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Scan Book Code',
            style: TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _bookCodeController,
                  style: TextStyle(color: Colors.white),
                  decoration: InputDecoration(
                    hintText: 'Enter book code (e.g., BK-001-123)',
                    hintStyle: TextStyle(color: Colors.white54),
                    filled: true,
                    fillColor: Color(0xFF0F1419),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide.none,
                    ),
                  ),
                  onSubmitted: (_) => _scanBook(),
                ),
              ),
              SizedBox(width: 12),
              ElevatedButton(
                onPressed: _scanBook,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue,
                  padding: EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                ),
                child: Text('Scan', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
          SizedBox(height: 8),
          Text(
            'Books scanned: ${_scannedBooks.length}/3',
            style: TextStyle(color: Colors.white70, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _buildScannedBooksList() {
    return Container(
      padding: EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Color(0xFF16213E),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Scanned Books',
            style: TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 12),
          ListView.separated(
            shrinkWrap: true,
            physics: NeverScrollableScrollPhysics(),
            itemCount: _scannedBooks.length,
            separatorBuilder: (context, index) => SizedBox(height: 8),
            itemBuilder: (context, index) {
              final book = _scannedBooks[index];
              return Container(
                padding: EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Color(0xFF0F1419),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            book['title'] ?? 'Unknown Title',
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            'Code: ${book['book_code']}',
                            style: TextStyle(color: Colors.white70, fontSize: 12),
                          ),
                          if (book['author_name'] != null)
                            Text(
                              'Author: ${book['author_name']}',
                              style: TextStyle(color: Colors.white70, fontSize: 12),
                            ),
                        ],
                      ),
                    ),
                    IconButton(
                      onPressed: () => _removeBook(index),
                      icon: Icon(Icons.remove_circle, color: Colors.red),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildBorrowButton() {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton(
        onPressed: _borrowBooks,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.green,
          padding: EdgeInsets.symmetric(vertical: 16),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
        ),
        child: Text(
          'Borrow ${_scannedBooks.length} Book${_scannedBooks.length > 1 ? 's' : ''}',
          style: TextStyle(
            color: Colors.white,
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}
