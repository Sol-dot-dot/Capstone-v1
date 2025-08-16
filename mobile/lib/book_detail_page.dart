import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class BookDetailPage extends StatefulWidget {
  final String bookId;
  final String studentId;

  const BookDetailPage({
    super.key,
    required this.bookId,
    required this.studentId,
  });

  @override
  State<BookDetailPage> createState() => _BookDetailPageState();
}

class _BookDetailPageState extends State<BookDetailPage> {
  final String baseUrl = 'http://10.0.2.2:8080';
  bool _isBookmarked = false;
  bool _isLoading = false;
  List<dynamic> _reviews = [];
  Map<String, dynamic> _book = {};

  @override
  void initState() {
    super.initState();
    _loadBookDetails();
  }

  Future<void> _loadBookDetails() async {
    await _loadBookInfo();
    await _loadReviews();
  }

  Future<void> _loadBookInfo() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/api_books.php?action=details&book_id=${widget.bookId}'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _book = data['book'] ?? {};
          });
          // Load bookmark status after book info is loaded
          _loadBookmarkStatus();
        }
      }
    } catch (e) {
      print('Error loading book info: $e');
    }
  }

  Future<void> _loadBookmarkStatus() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_profile.php?action=bookmarks&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          final bookmarks = data['bookmarks'] as List<dynamic>;
          setState(() {
            _isBookmarked = bookmarks.any((bookmark) => 
              bookmark['book_id'].toString() == widget.bookId);
          });
        }
      }
    } catch (e) {
      print('Error checking bookmark status: $e');
    }
  }

  Future<void> _loadReviews() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_books.php?action=reviews&book_id=${widget.bookId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _reviews = data['reviews'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading reviews: $e');
    }
  }

  Future<void> _toggleBookmark() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api_books.php'),
        body: {
          'action': 'toggle_bookmark',
          'student_id': widget.studentId,
          'book_id': widget.bookId,
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _isBookmarked = !_isBookmarked;
          });
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(_isBookmarked ? 'Added to bookmarks' : 'Removed from bookmarks'),
              backgroundColor: _isBookmarked ? Colors.green : Colors.orange,
            ),
          );
        }
      }
    } catch (e) {
      print('Error toggling bookmark: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _borrowBook() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api_borrowing.php'),
        body: {
          'action': 'borrow',
          'student_id': widget.studentId,
          'book_id': widget.bookId,
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Book borrowed successfully!'),
              backgroundColor: Colors.green,
            ),
          );
          Navigator.pop(context, true); // Return true to indicate refresh needed
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(data['error'] ?? 'Failed to borrow book'),
              backgroundColor: Colors.red,
            ),
          );
        }
      }
    } catch (e) {
      print('Error borrowing book: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Error borrowing book'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1A2E),
      appBar: AppBar(
        backgroundColor: const Color(0xFF16213E),
        title: Text(
          _book['title'] ?? 'Book Details',
          style: const TextStyle(color: Colors.white),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            onPressed: _isLoading ? null : _toggleBookmark,
            icon: Icon(
              _isBookmarked ? Icons.bookmark : Icons.bookmark_border,
              color: _isBookmarked ? Colors.amber : Colors.white,
            ),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Book cover and basic info
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 120,
                  height: 180,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(8),
                    image: DecorationImage(
                      image: NetworkImage(_book['cover_image'] ?? ''),
                      fit: BoxFit.cover,
                      onError: (exception, stackTrace) {},
                    ),
                    color: Colors.grey[700],
                  ),
                  child: _book['cover_image'] == null
                      ? const Icon(Icons.book, size: 60, color: Colors.white54)
                      : null,
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _book['title'] ?? 'Unknown Title',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'by ${_book['author_name'] ?? 'Unknown Author'}',
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Icon(Icons.star, color: Colors.amber, size: 20),
                          const SizedBox(width: 4),
                          Text(
                            '${_book['rating'] ?? '0.0'} (${_book['total_ratings'] ?? '0'} reviews)',
                            style: const TextStyle(color: Colors.white70),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Available: ${_book['available_copies'] ?? '0'}/${_book['total_copies'] ?? '0'}',
                        style: TextStyle(
                          color: (_book['available_copies'] ?? 0) > 0
                              ? Colors.green
                              : Colors.red,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Action buttons
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _isLoading || (_book['available_copies'] ?? 0) <= 0
                        ? null
                        : _borrowBook,
                    icon: const Icon(Icons.library_books),
                    label: Text(_isLoading ? 'Processing...' : 'Borrow Book'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0F3460),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Book details
            _buildDetailSection('Description', _book['description'] ?? 'No description available.'),
            _buildDetailSection('Publisher', _book['publisher'] ?? 'Unknown'),
            _buildDetailSection('Publication Year', _book['publication_year']?.toString() ?? 'Unknown'),
            _buildDetailSection('Pages', _book['pages']?.toString() ?? 'Unknown'),
            _buildDetailSection('Language', _book['language'] ?? 'Unknown'),
            _buildDetailSection('ISBN', _book['isbn'] ?? 'Unknown'),

            const SizedBox(height: 24),

            // Reviews section
            const Text(
              'Reviews',
              style: TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            _reviews.isEmpty
                ? const Text(
                    'No reviews yet.',
                    style: TextStyle(color: Colors.white70),
                  )
                : Column(
                    children: _reviews.map((review) => _buildReviewCard(review)).toList(),
                  ),
          ],
        ),
      ),
    );
  }

  Widget _buildDetailSection(String title, String content) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            content,
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 14,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReviewCard(Map<String, dynamic> review) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF16213E),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                review['student_id'] ?? 'Anonymous',
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const Spacer(),
              Row(
                children: List.generate(5, (index) {
                  return Icon(
                    index < (review['rating'] ?? 0) ? Icons.star : Icons.star_border,
                    color: Colors.amber,
                    size: 16,
                  );
                }),
              ),
            ],
          ),
          if (review['review_text'] != null && review['review_text'].isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              review['review_text'],
              style: const TextStyle(color: Colors.white70),
            ),
          ],
        ],
      ),
    );
  }
}
