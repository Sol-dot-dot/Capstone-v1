import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'book_detail_page.dart';

class RecommendationsPage extends StatefulWidget {
  final String studentId;

  const RecommendationsPage({super.key, required this.studentId});

  @override
  State<RecommendationsPage> createState() => _RecommendationsPageState();
}

class _RecommendationsPageState extends State<RecommendationsPage>
    with SingleTickerProviderStateMixin {
  final String baseUrl = 'http://10.0.2.2:8080';
  late TabController _tabController;
  
  List<dynamic> _personalizedBooks = [];
  List<dynamic> _trendingBooks = [];
  List<dynamic> _ragBooks = [];
  bool _isLoading = true;
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadRecommendations();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadRecommendations() async {
    setState(() {
      _isLoading = true;
    });

    try {
      await Future.wait([
        _loadPersonalizedRecommendations(),
        _loadTrendingBooks(),
        _loadRAGRecommendations(),
      ]);
    } catch (e) {
      print('Error loading recommendations: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadPersonalizedRecommendations() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_recommendations.php?action=personalized&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _personalizedBooks = data['recommendations'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading personalized recommendations: $e');
    }
  }

  Future<void> _loadTrendingBooks() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_recommendations.php?action=trending'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _trendingBooks = data['recommendations'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading trending books: $e');
    }
  }

  Future<void> _loadRAGRecommendations() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_recommendations.php?action=rag_enhanced&student_id=${widget.studentId}&query=$_searchQuery'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _ragBooks = data['recommendations'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading RAG recommendations: $e');
    }
  }

  Future<void> _trackInteraction(String bookId, String interactionType) async {
    try {
      await http.post(
        Uri.parse('$baseUrl/api_recommendations.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'action': 'track_interaction',
          'student_id': widget.studentId,
          'book_id': bookId,
          'interaction_type': interactionType,
        }),
      );
    } catch (e) {
      print('Error tracking interaction: $e');
    }
  }

  void _performRAGSearch() {
    if (_searchController.text.isNotEmpty) {
      setState(() {
        _searchQuery = _searchController.text;
      });
      _loadRAGRecommendations();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1A2E),
      appBar: AppBar(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Smart Recommendations', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: const Color(0xFF0F3460),
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'For You'),
            Tab(text: 'Trending'),
            Tab(text: 'AI Search'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF0F3460)),
            )
          : TabBarView(
              controller: _tabController,
              children: [
                _buildPersonalizedTab(),
                _buildTrendingTab(),
                _buildRAGSearchTab(),
              ],
            ),
    );
  }

  Widget _buildPersonalizedTab() {
    return RefreshIndicator(
      onRefresh: _loadPersonalizedRecommendations,
      child: _personalizedBooks.isEmpty
          ? _buildEmptyState('No personalized recommendations yet', 
              'Start borrowing books to get personalized suggestions!')
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _personalizedBooks.length,
              itemBuilder: (context, index) {
                final book = _personalizedBooks[index];
                return _buildRecommendationCard(book, 'Personalized');
              },
            ),
    );
  }

  Widget _buildTrendingTab() {
    return RefreshIndicator(
      onRefresh: _loadTrendingBooks,
      child: _trendingBooks.isEmpty
          ? _buildEmptyState('No trending books', 'Check back later for trending recommendations!')
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _trendingBooks.length,
              itemBuilder: (context, index) {
                final book = _trendingBooks[index];
                return _buildRecommendationCard(book, 'Trending');
              },
            ),
    );
  }

  Widget _buildRAGSearchTab() {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          color: const Color(0xFF16213E),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _searchController,
                  style: const TextStyle(color: Colors.white),
                  decoration: const InputDecoration(
                    hintText: 'Describe what you want to read...',
                    hintStyle: TextStyle(color: Colors.white54),
                    border: OutlineInputBorder(
                      borderSide: BorderSide(color: Colors.white30),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderSide: BorderSide(color: Colors.white30),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderSide: BorderSide(color: Color(0xFF0F3460)),
                    ),
                  ),
                  onSubmitted: (_) => _performRAGSearch(),
                ),
              ),
              const SizedBox(width: 8),
              IconButton(
                onPressed: _performRAGSearch,
                icon: const Icon(Icons.search, color: Colors.white),
                style: IconButton.styleFrom(
                  backgroundColor: const Color(0xFF0F3460),
                ),
              ),
            ],
          ),
        ),
        Expanded(
          child: _ragBooks.isEmpty
              ? _buildEmptyState('AI-Powered Search', 
                  'Enter a description of what you\'d like to read and get intelligent recommendations!')
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _ragBooks.length,
                  itemBuilder: (context, index) {
                    final book = _ragBooks[index];
                    return _buildRecommendationCard(book, 'AI Match');
                  },
                ),
        ),
      ],
    );
  }

  Widget _buildRecommendationCard(Map<String, dynamic> book, String type) {
    return Card(
      color: const Color(0xFF16213E),
      margin: const EdgeInsets.only(bottom: 16),
      child: InkWell(
        onTap: () {
          _trackInteraction(book['id'].toString(), 'view');
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => BookDetailPage(
                bookId: book['id'].toString(),
                studentId: widget.studentId,
              ),
            ),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 80,
                height: 120,
                decoration: BoxDecoration(
                  color: Color(int.parse(book['category_color']?.replaceAll('#', '0xFF') ?? '0xFF0F3460')),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Center(
                  child: Icon(
                    Icons.book,
                    color: Colors.white,
                    size: 40,
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F3460),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            type,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                        const Spacer(),
                        if (book['confidence_score'] != null)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.green.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              '${(double.parse(book['confidence_score'].toString()) * 100).round()}% match',
                              style: const TextStyle(
                                color: Colors.green,
                                fontSize: 10,
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      book['title'] ?? 'Unknown Title',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      book['authors'] ?? 'Unknown Author',
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 14,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(
                          Icons.category,
                          color: Colors.white54,
                          size: 16,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          book['category_name'] ?? 'Unknown',
                          style: const TextStyle(
                            color: Colors.white54,
                            fontSize: 12,
                          ),
                        ),
                        const Spacer(),
                        if (book['avg_rating'] != null && double.parse(book['avg_rating'].toString()) > 0)
                          Row(
                            children: [
                              const Icon(
                                Icons.star,
                                color: Colors.amber,
                                size: 16,
                              ),
                              const SizedBox(width: 2),
                              Text(
                                book['avg_rating'].toString(),
                                style: const TextStyle(
                                  color: Colors.white70,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                      ],
                    ),
                    if (book['description'] != null) ...[
                      const SizedBox(height: 8),
                      Text(
                        book['description'],
                        style: const TextStyle(
                          color: Colors.white60,
                          fontSize: 12,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState(String title, String subtitle) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.auto_awesome,
              size: 64,
              color: Colors.white54,
            ),
            const SizedBox(height: 16),
            Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              subtitle,
              style: const TextStyle(
                color: Colors.white70,
                fontSize: 14,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
