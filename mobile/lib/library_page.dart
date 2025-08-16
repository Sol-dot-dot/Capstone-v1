import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'book_detail_page.dart';

class LibraryPage extends StatefulWidget {
  final String studentId;

  const LibraryPage({super.key, required this.studentId});

  @override
  State<LibraryPage> createState() => _LibraryPageState();
}

class _LibraryPageState extends State<LibraryPage> {
  final String baseUrl = 'http://10.0.2.2:8080';
  List<dynamic> _borrowedBooks = [];
  List<dynamic> _overdueBooks = [];
  bool _isLoading = true;
  String _selectedTab = 'borrowed';

  @override
  void initState() {
    super.initState();
    _loadLibraryData();
  }

  Future<void> _loadLibraryData() async {
    setState(() {
      _isLoading = true;
    });

    try {
      await Future.wait([
        _loadBorrowedBooks(),
        _loadOverdueBooks(),
      ]);
    } catch (e) {
      print('Error loading library data: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadBorrowedBooks() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_borrowing.php?action=active&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _borrowedBooks = data['borrowings'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading borrowed books: $e');
    }
  }

  Future<void> _loadOverdueBooks() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_borrowing.php?action=overdue&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _overdueBooks = data['overdue_books'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading overdue books: $e');
    }
  }

  Future<void> _returnBook(String bookId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api_borrowing.php'),
        body: {
          'action': 'return',
          'student_id': widget.studentId,
          'book_id': bookId,
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Book returned successfully!'),
              backgroundColor: Colors.green,
            ),
          );
          _loadLibraryData(); // Refresh data
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['error'] ?? 'Failed to return book'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      print('Error returning book: $e');
    }
  }

  Future<void> _renewBook(String bookId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api_borrowing.php'),
        body: {
          'action': 'renew',
          'student_id': widget.studentId,
          'book_id': bookId,
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Book renewed successfully!'),
              backgroundColor: Colors.green,
            ),
          );
          _loadLibraryData(); // Refresh data
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['error'] ?? 'Failed to renew book'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      print('Error renewing book: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1A2E),
      appBar: AppBar(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('My Library', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Column(
        children: [
          // Tab selector
          Container(
            margin: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF16213E),
              borderRadius: BorderRadius.circular(25),
            ),
            child: Row(
              children: [
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() => _selectedTab = 'borrowed'),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      decoration: BoxDecoration(
                        color: _selectedTab == 'borrowed'
                            ? const Color(0xFF0F3460)
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(25),
                      ),
                      child: Text(
                        'Borrowed (${_borrowedBooks.length})',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: _selectedTab == 'borrowed' ? Colors.white : Colors.white70,
                          fontWeight: _selectedTab == 'borrowed' ? FontWeight.bold : FontWeight.normal,
                        ),
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() => _selectedTab = 'overdue'),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      decoration: BoxDecoration(
                        color: _selectedTab == 'overdue'
                            ? const Color(0xFF0F3460)
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(25),
                      ),
                      child: Text(
                        'Overdue (${_overdueBooks.length})',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: _selectedTab == 'overdue' ? Colors.white : Colors.white70,
                          fontWeight: _selectedTab == 'overdue' ? FontWeight.bold : FontWeight.normal,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Content
          Expanded(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(color: Color(0xFF0F3460)),
                  )
                : RefreshIndicator(
                    onRefresh: _loadLibraryData,
                    child: _selectedTab == 'borrowed'
                        ? _buildBorrowedBooks()
                        : _buildOverdueBooks(),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildBorrowedBooks() {
    if (_borrowedBooks.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.library_books, size: 64, color: Colors.white54),
            SizedBox(height: 16),
            Text(
              'No borrowed books',
              style: TextStyle(color: Colors.white70, fontSize: 18),
            ),
            Text(
              'Visit the home page to borrow books',
              style: TextStyle(color: Colors.white54, fontSize: 14),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _borrowedBooks.length,
      itemBuilder: (context, index) {
        final borrowing = _borrowedBooks[index];
        return _buildBorrowingCard(borrowing, false);
      },
    );
  }

  Widget _buildOverdueBooks() {
    if (_overdueBooks.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.check_circle, size: 64, color: Colors.green),
            SizedBox(height: 16),
            Text(
              'No overdue books',
              style: TextStyle(color: Colors.white70, fontSize: 18),
            ),
            Text(
              'Great job keeping up with returns!',
              style: TextStyle(color: Colors.white54, fontSize: 14),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _overdueBooks.length,
      itemBuilder: (context, index) {
        final borrowing = _overdueBooks[index];
        return _buildBorrowingCard(borrowing, true);
      },
    );
  }

  Widget _buildBorrowingCard(Map<String, dynamic> borrowing, bool isOverdue) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF16213E),
        borderRadius: BorderRadius.circular(12),
        border: isOverdue
            ? Border.all(color: Colors.red, width: 1)
            : null,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 60,
                height: 80,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(6),
                  image: borrowing['cover_image'] != null
                      ? DecorationImage(
                          image: NetworkImage(borrowing['cover_image']),
                          fit: BoxFit.cover,
                        )
                      : null,
                  color: Colors.grey[700],
                ),
                child: borrowing['cover_image'] == null
                    ? const Icon(Icons.book, color: Colors.white54)
                    : null,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      borrowing['title'] ?? 'Unknown Title',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'by ${borrowing['author_name'] ?? 'Unknown Author'}',
                      style: const TextStyle(color: Colors.white70, fontSize: 14),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(
                          isOverdue ? Icons.warning : Icons.schedule,
                          size: 16,
                          color: isOverdue ? Colors.red : Colors.orange,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          isOverdue ? 'Overdue' : 'Due: ${borrowing['due_date']}',
                          style: TextStyle(
                            color: isOverdue ? Colors.red : Colors.orange,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _returnBook(borrowing['book_id'].toString()),
                  icon: const Icon(Icons.assignment_return, size: 16),
                  label: const Text('Return'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white54),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _renewBook(borrowing['book_id'].toString()),
                  icon: const Icon(Icons.refresh, size: 16),
                  label: const Text('Renew'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF0F3460),
                    side: const BorderSide(color: Color(0xFF0F3460)),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
