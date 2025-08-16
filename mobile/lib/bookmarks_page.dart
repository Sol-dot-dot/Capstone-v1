import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'book_detail_page.dart';

class BookmarksPage extends StatefulWidget {
  final String studentId;

  const BookmarksPage({super.key, required this.studentId});

  @override
  State<BookmarksPage> createState() => _BookmarksPageState();
}

class _BookmarksPageState extends State<BookmarksPage> {
  final String baseUrl = 'http://10.0.2.2:8080';
  List<dynamic> _bookmarks = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadBookmarks();
  }

  Future<void> _loadBookmarks() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_profile.php?action=bookmarks&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _bookmarks = data['bookmarks'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading bookmarks: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _removeBookmark(String bookId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api_books.php'),
        body: {
          'action': 'toggle_bookmark',
          'student_id': widget.studentId,
          'book_id': bookId,
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Removed from bookmarks'),
              backgroundColor: Colors.orange,
            ),
          );
          _loadBookmarks(); // Refresh bookmarks
        }
      }
    } catch (e) {
      print('Error removing bookmark: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1A2E),
      appBar: AppBar(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Bookmarks', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF0F3460)),
            )
          : RefreshIndicator(
              onRefresh: _loadBookmarks,
              child: _bookmarks.isEmpty
                  ? const Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.bookmark_border, size: 64, color: Colors.white54),
                          SizedBox(height: 16),
                          Text(
                            'No bookmarks yet',
                            style: TextStyle(color: Colors.white70, fontSize: 18),
                          ),
                          Text(
                            'Bookmark books to save them for later',
                            style: TextStyle(color: Colors.white54, fontSize: 14),
                          ),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _bookmarks.length,
                      itemBuilder: (context, index) {
                        final book = _bookmarks[index];
                        return _buildBookmarkCard(book);
                      },
                    ),
            ),
    );
  }

  Widget _buildBookmarkCard(Map<String, dynamic> book) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF16213E),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          GestureDetector(
            onTap: () async {
              final result = await Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => BookDetailPage(
                    bookId: book['id'].toString(),
                    studentId: widget.studentId,
                  ),
                ),
              );
              if (result == true) {
                _loadBookmarks(); // Refresh if bookmark status changed
              }
            },
            child: Container(
              width: 60,
              height: 80,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(6),
                image: book['cover_image'] != null
                    ? DecorationImage(
                        image: NetworkImage(book['cover_image']),
                        fit: BoxFit.cover,
                      )
                    : null,
                color: Colors.grey[700],
              ),
              child: book['cover_image'] == null
                  ? const Icon(Icons.book, color: Colors.white54)
                  : null,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: GestureDetector(
              onTap: () async {
                final result = await Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => BookDetailPage(
                      bookId: book['id'].toString(),
                      studentId: widget.studentId,
                    ),
                  ),
                );
                if (result == true) {
                  _loadBookmarks(); // Refresh if bookmark status changed
                }
              },
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    book['title'] ?? 'Unknown Title',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'by ${book['author_name'] ?? 'Unknown Author'}',
                    style: const TextStyle(color: Colors.white70, fontSize: 14),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Icon(Icons.star, color: Colors.amber, size: 16),
                      const SizedBox(width: 4),
                      Text(
                        '${book['rating'] ?? '0.0'}',
                        style: const TextStyle(color: Colors.white70, fontSize: 12),
                      ),
                      const SizedBox(width: 16),
                      Text(
                        'Available: ${book['available_copies'] ?? '0'}',
                        style: TextStyle(
                          color: (book['available_copies'] ?? 0) > 0
                              ? Colors.green
                              : Colors.red,
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          IconButton(
            onPressed: () => _removeBookmark(book['id'].toString()),
            icon: const Icon(Icons.bookmark, color: Colors.amber),
            tooltip: 'Remove bookmark',
          ),
        ],
      ),
    );
  }
}
