import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'book_detail_page.dart';
import 'bookmarks_page.dart';
import 'profile_page.dart';
import 'recommendations_page.dart';
import 'analytics_page.dart';
import 'search_page.dart';
import 'all_books_page.dart';
import 'main.dart';

class LandingPage extends StatefulWidget {
  final String studentId;
  
  const LandingPage({super.key, required this.studentId});

  @override
  State<LandingPage> createState() => _LandingPageState();
}

class _LandingPageState extends State<LandingPage> {
  int _currentIndex = 0;
  List<dynamic> _books = [];
  List<dynamic> _categories = [];
  List<dynamic> _recommendations = [];
  List<dynamic> _activeBorrowings = [];
  bool _isLoading = true;
  
  final String baseUrl = 'http://10.0.2.2:8080';
  
  @override
  void initState() {
    super.initState();
    _loadData();
  }
  
  Future<void> _loadData() async {
    try {
      await Future.wait([
        _loadBooks(),
        _loadCategories(),
        _loadRecommendations(),
        _loadActiveBorrowings(),
      ]);
    } catch (e) {
      print('Error loading data: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }
  
  Future<void> _loadBooks() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/api_books.php?action=all'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _books = data['books'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading books: $e');
      setState(() {
        _books = [];
      });
    }
  }
  
  Future<void> _loadCategories() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/api_books.php?action=categories'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _categories = data['categories'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading categories: $e');
    }
  }
  
  Future<void> _loadRecommendations() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/api_books.php?action=recommendations&student_id=${widget.studentId}'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _recommendations = data['recommendations'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading recommendations: $e');
    }
  }
  
  Future<void> _loadActiveBorrowings() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/api_student.php?action=borrowings&student_id=${widget.studentId}'));
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _activeBorrowings = (data['borrowings'] ?? []).where((b) => b['status'] == 'active').toList();
          });
        }
      }
    } catch (e) {
      print('Error loading active borrowings: $e');
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1A2E),
      body: SafeArea(
        child: _buildCurrentScreen(),
      ),
      bottomNavigationBar: _buildBottomNavBar(),
    );
  }

  Widget _buildCurrentScreen() {
    switch (_currentIndex) {
      case 0:
        return _buildHomeScreen();
      case 1:
        return RecommendationsPage(studentId: widget.studentId);
      case 2:
        return AnalyticsPage(studentId: widget.studentId);
      case 3:
        return BookmarksPage(studentId: widget.studentId);
      case 4:
        // AI Chat placeholder - will be implemented later
        return _buildHomeScreen();
      case 5:
        return ProfilePage(studentId: widget.studentId);
      default:
        return _buildHomeScreen();
    }
  }

  Widget _buildHomeScreen() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(
          valueColor: AlwaysStoppedAnimation<Color>(Color(0xFF4A90E2)),
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header with profile and notification
          _buildHeader(),
          const SizedBox(height: 20),
          
          // Search bar
          _buildSearchBar(),
          const SizedBox(height: 30),
          
          // Top Available for you section
          _buildSectionHeader('Top Available for you', 'View All'),
          const SizedBox(height: 15),
          _buildTopBooksGrid(),
          const SizedBox(height: 30),
          
          // Pick from Popular Genres
          _buildSectionHeader('Pick from Popular Genres', 'View All'),
          const SizedBox(height: 15),
          _buildGenresGrid(),
          const SizedBox(height: 30),
          
          // Your Recently Borrowed Books
          _buildSectionHeader('Your Recently Borrowed Books', 'View All'),
          const SizedBox(height: 15),
          _buildRecentBooksGrid(),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Row(
      children: [
        CircleAvatar(
          radius: 25,
          backgroundColor: Colors.grey[800],
          child: const Icon(Icons.person, color: Colors.white, size: 30),
        ),
        const SizedBox(width: 15),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Hello Student!',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const Text(
                'Find your next great read',
                style: TextStyle(
                  color: Colors.grey,
                  fontSize: 14,
                ),
              ),
            ],
          ),
        ),
        IconButton(
          onPressed: () {},
          icon: const Icon(Icons.notifications_outlined, color: Colors.white, size: 28),
        ),
        IconButton(
          onPressed: () => _showLogoutDialog(context),
          icon: const Icon(Icons.logout, color: Colors.white, size: 24),
        ),
      ],
    );
  }

  Widget _buildSearchBar() {
    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => SearchPage(studentId: widget.studentId),
          ),
        );
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
        decoration: BoxDecoration(
          color: const Color(0xFF16213E),
          borderRadius: BorderRadius.circular(15),
        ),
        child: const Row(
          children: [
            Icon(Icons.search, color: Colors.grey, size: 24),
            SizedBox(width: 15),
            Text(
              'Search by Book title, Author, etc',
              style: TextStyle(color: Colors.grey, fontSize: 16),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title, String action) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          title,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        GestureDetector(
          onTap: () {
            if (action == 'View All') {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => AllBooksPage(studentId: widget.studentId),
                ),
              );
            }
          },
          child: Text(
            action,
            style: const TextStyle(
              color: Colors.blue,
              fontSize: 14,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildTopBooksGrid() {
    if (_books.isEmpty) {
      return Container(
        height: 200,
        child: const Center(
          child: Text(
            'No books available',
            style: TextStyle(color: Colors.grey, fontSize: 16),
          ),
        ),
      );
    }

    return SizedBox(
      height: 200,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: _books.length > 5 ? 5 : _books.length,
        itemBuilder: (context, index) {
          final book = _books[index];
          final categoryColor = book['category_color'] != null 
              ? Color(int.parse(book['category_color'].substring(1), radix: 16) + 0xFF000000)
              : Colors.blue;
          
          return GestureDetector(
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
                _loadData(); // Refresh data if needed
              }
            },
            child: Container(
              width: 100,
              margin: const EdgeInsets.only(right: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 100,
                    height: 140,
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      image: book['cover_image'] != null
                          ? DecorationImage(
                              image: NetworkImage(book['cover_image']),
                              fit: BoxFit.cover,
                            )
                          : null,
                      color: Colors.grey[700],
                    ),
                    child: book['cover_image'] == null
                        ? const Icon(
                            Icons.book,
                            size: 40,
                            color: Colors.white54,
                          )
                        : null,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    book['title'] ?? 'Unknown',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    'by ${book['author_name'] ?? 'Unknown'}',
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 10,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Icon(
                        Icons.star,
                        color: Colors.amber,
                        size: 12,
                      ),
                      const SizedBox(width: 2),
                      Text(
                        '${book['rating'] ?? '0.0'}',
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 10,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildGenresGrid() {
    if (_categories.isEmpty) {
      return Container(
        height: 100,
        child: const Center(
          child: Text(
            'No categories available',
            style: TextStyle(color: Colors.grey, fontSize: 16),
          ),
        ),
      );
    }

    return SizedBox(
      height: 100,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: _categories.length > 6 ? 6 : _categories.length,
        itemBuilder: (context, index) {
          final category = _categories[index];
          final categoryColor = category['color'] != null 
              ? Color(int.parse(category['color'].substring(1), radix: 16) + 0xFF000000)
              : Colors.blue;
          
          return Container(
            width: 80,
            margin: const EdgeInsets.only(right: 15),
            child: Column(
              children: [
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    color: categoryColor,
                    borderRadius: BorderRadius.circular(15),
                  ),
                  child: Icon(
                    _getCategoryIcon(category['icon'] as String? ?? 'book'),
                    color: Colors.white,
                    size: 30,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  category['name'] as String,
                  style: const TextStyle(color: Colors.white, fontSize: 10),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  IconData _getCategoryIcon(String iconName) {
    switch (iconName.toLowerCase()) {
      case 'person':
        return Icons.person;
      case 'auto_stories':
        return Icons.auto_stories;
      case 'engineering':
        return Icons.engineering;
      case 'history_edu':
        return Icons.history_edu;
      case 'science':
        return Icons.science;
      case 'computer':
        return Icons.computer;
      case 'menu_book':
        return Icons.menu_book;
      case 'business':
        return Icons.business;
      default:
        return Icons.book;
    }
  }

  Widget _buildRecentBooksGrid() {
    if (_activeBorrowings.isEmpty) {
      return Container(
        height: 120,
        child: const Center(
          child: Text(
            'No active borrowings',
            style: TextStyle(color: Colors.white70),
          ),
        ),
      );
    }

    return SizedBox(
      height: 120,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: _activeBorrowings.length,
        itemBuilder: (context, index) {
          final borrowing = _activeBorrowings[index];
          final categoryColor = borrowing['category_color'] != null 
              ? Color(int.parse(borrowing['category_color'].substring(1), radix: 16) + 0xFF000000)
              : Colors.blue;
          
          String statusText = borrowing['status_display'] ?? 'Active';
          if (borrowing['days_remaining'] != null) {
            int daysLeft = borrowing['days_remaining'] as int;
            if (daysLeft > 0) {
              statusText = '$daysLeft Days Left';
            } else if (borrowing['is_overdue'] == true) {
              statusText = 'Overdue';
            }
          }
          
          return Container(
            width: 100,
            margin: const EdgeInsets.only(right: 15),
            child: Column(
              children: [
                Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: categoryColor,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Center(
                    child: Text(
                      (borrowing['title'] as String).split(' ').first,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 10,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  statusText,
                  style: TextStyle(
                    color: borrowing['is_overdue'] == true ? Colors.red : Colors.grey, 
                    fontSize: 9
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildBottomNavBar() {
    return BottomNavigationBar(
      type: BottomNavigationBarType.fixed,
      backgroundColor: const Color(0xFF16213E),
      selectedItemColor: const Color(0xFF0F3460),
      unselectedItemColor: Colors.white54,
      currentIndex: _currentIndex,
      onTap: (index) {
        setState(() {
          if (index == 4) {
            // AI Chat - placeholder for now
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('AI Chat coming soon!'),
                backgroundColor: Color(0xFF0F3460),
              ),
            );
          } else {
            _currentIndex = index;
          }
        });
      },
      items: const [
        BottomNavigationBarItem(
          icon: Icon(Icons.home),
          label: 'Home',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.auto_awesome),
          label: 'AI Recommendations',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.analytics),
          label: 'Analytics',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.bookmark),
          label: 'Bookmarks',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.smart_toy),
          label: 'AI Chat',
        ),
        BottomNavigationBarItem(
          icon: Icon(Icons.person),
          label: 'Profile',
        ),
      ],
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          backgroundColor: const Color(0xFF16213E),
          title: const Text('Logout', style: TextStyle(color: Colors.white)),
          content: const Text(
            'Are you sure you want to logout?',
            style: TextStyle(color: Colors.white70),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Cancel', style: TextStyle(color: Colors.white70)),
            ),
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                Navigator.of(context).pushAndRemoveUntil(
                  MaterialPageRoute(builder: (context) => const CapstoneLoginApp()),
                  (route) => false,
                );
              },
              child: const Text('Logout', style: TextStyle(color: Colors.red)),
            ),
          ],
        );
      },
    );
  }

}

