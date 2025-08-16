import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'book_detail_page.dart';

class SearchPage extends StatefulWidget {
  final String studentId;
  final String? initialQuery;
  final String? initialCategory;

  const SearchPage({super.key, required this.studentId, this.initialQuery, this.initialCategory});

  @override
  State<SearchPage> createState() => _SearchPageState();
}

class _SearchPageState extends State<SearchPage> with SingleTickerProviderStateMixin {
  final String baseUrl = 'http://10.0.2.2:8080';
  late TabController _tabController;
  
  final TextEditingController _searchController = TextEditingController();
  final TextEditingController _titleController = TextEditingController();
  final TextEditingController _authorController = TextEditingController();
  final TextEditingController _isbnController = TextEditingController();
  
  List<dynamic> _searchResults = [];
  List<dynamic> _suggestions = [];
  Map<String, dynamic> _filters = {};
  bool _isLoading = false;
  bool _showSuggestions = false;
  
  String _selectedCategory = '';
  String _selectedAuthor = '';
  String _selectedAvailability = 'all';
  String _selectedSortBy = 'relevance';
  double _minRating = 0;
  
  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    if (widget.initialQuery != null) {
      _searchController.text = widget.initialQuery!;
      _performSearch();
    }
    if (widget.initialCategory != null) {
      _selectedCategory = widget.initialCategory!;
      _performSearch();
    }
    _loadFilters();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    _titleController.dispose();
    _authorController.dispose();
    _isbnController.dispose();
    super.dispose();
  }

  Future<void> _loadFilters() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_search.php?action=filters'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _filters = data['filters'] ?? {};
          });
        }
      }
    } catch (e) {
      print('Error loading filters: $e');
    }
  }

  Future<void> _performSearch() async {
    if (_searchController.text.isEmpty) return;
    
    setState(() {
      _isLoading = true;
      _showSuggestions = false;
    });

    try {
      final queryParams = {
        'action': 'search',
        'query': _searchController.text,
        'category': _selectedCategory,
        'author': _selectedAuthor,
        'min_rating': _minRating.toString(),
        'availability': _selectedAvailability,
        'sort_by': _selectedSortBy,
        'limit': '20',
      };

      final uri = Uri.parse('$baseUrl/api_search.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _searchResults = data['results'] ?? [];
          });
          
          // Log search
          _logSearch(_searchController.text, _searchResults.length);
        }
      }
    } catch (e) {
      print('Error performing search: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _performAdvancedSearch() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final queryParams = {
        'action': 'advanced',
        'title': _titleController.text,
        'author': _authorController.text,
        'category': _selectedCategory,
        'isbn': _isbnController.text,
        'min_rating': _minRating.toString(),
        'availability': _selectedAvailability,
      };

      final uri = Uri.parse('$baseUrl/api_search.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _searchResults = data['results'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error performing advanced search: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _getSuggestions(String query) async {
    if (query.length < 2) {
      setState(() {
        _suggestions = [];
        _showSuggestions = false;
      });
      return;
    }

    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_search.php?action=suggestions&query=$query'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _suggestions = data['suggestions'] ?? [];
            _showSuggestions = _suggestions.isNotEmpty;
          });
        }
      }
    } catch (e) {
      print('Error getting suggestions: $e');
    }
  }

  Future<void> _logSearch(String query, int resultCount) async {
    try {
      await http.post(
        Uri.parse('$baseUrl/api_search.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'action': 'log_search',
          'student_id': widget.studentId,
          'query': query,
          'search_type': 'general',
          'results_count': resultCount,
        }),
      );
    } catch (e) {
      print('Error logging search: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1A2E),
      appBar: AppBar(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Smart Search', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: const Color(0xFF0F3460),
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Quick Search'),
            Tab(text: 'Advanced'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildQuickSearchTab(),
          _buildAdvancedSearchTab(),
        ],
      ),
    );
  }

  Widget _buildQuickSearchTab() {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          color: const Color(0xFF16213E),
          child: Column(
            children: [
              TextField(
                controller: _searchController,
                style: const TextStyle(color: Colors.white),
                decoration: InputDecoration(
                  hintText: 'Search books, authors, categories...',
                  hintStyle: const TextStyle(color: Colors.white54),
                  prefixIcon: const Icon(Icons.search, color: Colors.white54),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, color: Colors.white54),
                          onPressed: () {
                            _searchController.clear();
                            setState(() {
                              _searchResults = [];
                              _showSuggestions = false;
                            });
                          },
                        )
                      : null,
                  border: const OutlineInputBorder(
                    borderSide: BorderSide(color: Colors.white30),
                  ),
                  enabledBorder: const OutlineInputBorder(
                    borderSide: BorderSide(color: Colors.white30),
                  ),
                  focusedBorder: const OutlineInputBorder(
                    borderSide: BorderSide(color: Color(0xFF0F3460)),
                  ),
                ),
                onChanged: (value) {
                  _getSuggestions(value);
                },
                onSubmitted: (_) => _performSearch(),
              ),
              const SizedBox(height: 16),
              _buildFilters(),
            ],
          ),
        ),
        if (_showSuggestions) _buildSuggestions(),
        Expanded(
          child: _isLoading
              ? const Center(
                  child: CircularProgressIndicator(color: Color(0xFF0F3460)),
                )
              : _searchResults.isEmpty
                  ? _buildEmptyState()
                  : _buildSearchResults(),
        ),
      ],
    );
  }

  Widget _buildAdvancedSearchTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Advanced Search',
            style: TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 20),
          _buildAdvancedField('Title', _titleController, Icons.book),
          const SizedBox(height: 16),
          _buildAdvancedField('Author', _authorController, Icons.person),
          const SizedBox(height: 16),
          _buildAdvancedField('ISBN', _isbnController, Icons.qr_code),
          const SizedBox(height: 16),
          _buildCategoryDropdown(),
          const SizedBox(height: 16),
          _buildRatingSlider(),
          const SizedBox(height: 16),
          _buildAvailabilityFilter(),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _performAdvancedSearch,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0F3460),
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
              child: const Text(
                'Search',
                style: TextStyle(color: Colors.white, fontSize: 16),
              ),
            ),
          ),
          const SizedBox(height: 20),
          if (_searchResults.isNotEmpty) ...[
            const Text(
              'Search Results',
              style: TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            ..._searchResults.map((book) => _buildBookCard(book)).toList(),
          ],
        ],
      ),
    );
  }

  Widget _buildFilters() {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _buildFilterChip('Category', _selectedCategory, () => _showCategoryDialog()),
          _buildFilterChip('Sort', _selectedSortBy, () => _showSortDialog()),
          _buildFilterChip('Rating', _minRating > 0 ? '${_minRating.toInt()}+' : 'Any', () => _showRatingDialog()),
          _buildFilterChip('Availability', _selectedAvailability, () => _showAvailabilityDialog()),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String value, VoidCallback onTap) {
    final isActive = (label == 'Category' && _selectedCategory.isNotEmpty) ||
                     (label == 'Sort' && _selectedSortBy != 'relevance') ||
                     (label == 'Rating' && _minRating > 0) ||
                     (label == 'Availability' && _selectedAvailability != 'all');

    return Container(
      margin: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text('$label: ${_getDisplayValue(label, value)}'),
        selected: isActive,
        onSelected: (_) => onTap(),
        backgroundColor: const Color(0xFF1A1A2E),
        selectedColor: const Color(0xFF0F3460),
        labelStyle: TextStyle(
          color: isActive ? Colors.white : Colors.white70,
          fontSize: 12,
        ),
      ),
    );
  }

  String _getDisplayValue(String label, String value) {
    switch (label) {
      case 'Category':
        return value.isEmpty ? 'All' : value;
      case 'Sort':
        return value == 'relevance' ? 'Relevance' : value;
      case 'Rating':
        return _minRating > 0 ? '${_minRating.toInt()}+' : 'Any';
      case 'Availability':
        return value == 'all' ? 'All' : value;
      default:
        return value;
    }
  }

  Widget _buildSuggestions() {
    return Container(
      color: const Color(0xFF16213E),
      child: ListView.builder(
        shrinkWrap: true,
        itemCount: _suggestions.length,
        itemBuilder: (context, index) {
          final suggestion = _suggestions[index];
          return ListTile(
            leading: Icon(
              _getSuggestionIcon(suggestion['type']),
              color: Colors.white54,
              size: 20,
            ),
            title: Text(
              suggestion['suggestion'],
              style: const TextStyle(color: Colors.white, fontSize: 14),
            ),
            subtitle: Text(
              suggestion['type'].toUpperCase(),
              style: const TextStyle(color: Colors.white54, fontSize: 10),
            ),
            onTap: () {
              _searchController.text = suggestion['suggestion'];
              setState(() {
                _showSuggestions = false;
              });
              _performSearch();
            },
          );
        },
      ),
    );
  }

  IconData _getSuggestionIcon(String type) {
    switch (type) {
      case 'book':
        return Icons.book;
      case 'author':
        return Icons.person;
      case 'category':
        return Icons.category;
      default:
        return Icons.search;
    }
  }

  Widget _buildSearchResults() {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _searchResults.length,
      itemBuilder: (context, index) {
        final book = _searchResults[index];
        return _buildBookCard(book);
      },
    );
  }

  Widget _buildBookCard(Map<String, dynamic> book) {
    return Card(
      color: const Color(0xFF16213E),
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () {
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
                width: 60,
                height: 90,
                decoration: BoxDecoration(
                  color: Color(int.parse(book['category_color']?.replaceAll('#', '0xFF') ?? '0xFF0F3460')),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Center(
                  child: Icon(Icons.book, color: Colors.white, size: 30),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
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
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      book['authors'] ?? 'Unknown Author',
                      style: const TextStyle(color: Colors.white70, fontSize: 14),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F3460),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            book['category_name'] ?? 'Unknown',
                            style: const TextStyle(color: Colors.white, fontSize: 10),
                          ),
                        ),
                        const Spacer(),
                        if (book['avg_rating'] != null && double.parse(book['avg_rating'].toString()) > 0) ...[
                          const Icon(Icons.star, color: Colors.amber, size: 16),
                          const SizedBox(width: 2),
                          Text(
                            book['avg_rating'].toString(),
                            style: const TextStyle(color: Colors.white70, fontSize: 12),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(
                          book['is_available'] ? Icons.check_circle : Icons.cancel,
                          color: book['is_available'] ? Colors.green : Colors.red,
                          size: 16,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          book['is_available'] ? 'Available' : 'Borrowed',
                          style: TextStyle(
                            color: book['is_available'] ? Colors.green : Colors.red,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAdvancedField(String label, TextEditingController controller, IconData icon) {
    return TextField(
      controller: controller,
      style: const TextStyle(color: Colors.white),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(color: Colors.white70),
        prefixIcon: Icon(icon, color: Colors.white54),
        border: const OutlineInputBorder(
          borderSide: BorderSide(color: Colors.white30),
        ),
        enabledBorder: const OutlineInputBorder(
          borderSide: BorderSide(color: Colors.white30),
        ),
        focusedBorder: const OutlineInputBorder(
          borderSide: BorderSide(color: Color(0xFF0F3460)),
        ),
      ),
    );
  }

  Widget _buildCategoryDropdown() {
    final categories = _filters['categories'] as List<dynamic>? ?? [];
    
    return DropdownButtonFormField<String>(
      value: _selectedCategory.isEmpty ? null : _selectedCategory,
      dropdownColor: const Color(0xFF16213E),
      decoration: const InputDecoration(
        labelText: 'Category',
        labelStyle: TextStyle(color: Colors.white70),
        border: OutlineInputBorder(borderSide: BorderSide(color: Colors.white30)),
        enabledBorder: OutlineInputBorder(borderSide: BorderSide(color: Colors.white30)),
        focusedBorder: OutlineInputBorder(borderSide: BorderSide(color: Color(0xFF0F3460))),
      ),
      style: const TextStyle(color: Colors.white),
      items: [
        const DropdownMenuItem<String>(
          value: '',
          child: Text('All Categories', style: TextStyle(color: Colors.white)),
        ),
        ...categories.map((category) => DropdownMenuItem<String>(
          value: category['name'],
          child: Text(category['name'], style: const TextStyle(color: Colors.white)),
        )).toList(),
      ],
      onChanged: (value) {
        setState(() {
          _selectedCategory = value ?? '';
        });
      },
    );
  }

  Widget _buildRatingSlider() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Minimum Rating: ${_minRating.toInt()}${_minRating > 0 ? '+' : ' (Any)'}',
          style: const TextStyle(color: Colors.white70, fontSize: 14),
        ),
        Slider(
          value: _minRating,
          min: 0,
          max: 5,
          divisions: 5,
          activeColor: const Color(0xFF0F3460),
          inactiveColor: Colors.white30,
          onChanged: (value) {
            setState(() {
              _minRating = value;
            });
          },
        ),
      ],
    );
  }

  Widget _buildAvailabilityFilter() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Availability',
          style: TextStyle(color: Colors.white70, fontSize: 14),
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: RadioListTile<String>(
                title: const Text('All', style: TextStyle(color: Colors.white, fontSize: 12)),
                value: 'all',
                groupValue: _selectedAvailability,
                activeColor: const Color(0xFF0F3460),
                onChanged: (value) {
                  setState(() {
                    _selectedAvailability = value!;
                  });
                },
              ),
            ),
            Expanded(
              child: RadioListTile<String>(
                title: const Text('Available', style: TextStyle(color: Colors.white, fontSize: 12)),
                value: 'available',
                groupValue: _selectedAvailability,
                activeColor: const Color(0xFF0F3460),
                onChanged: (value) {
                  setState(() {
                    _selectedAvailability = value!;
                  });
                },
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.search_off, size: 64, color: Colors.white54),
          const SizedBox(height: 16),
          Text(
            _searchController.text.isEmpty ? 'Enter a search term' : 'No results found',
            style: const TextStyle(color: Colors.white, fontSize: 18),
          ),
          const SizedBox(height: 8),
          Text(
            _searchController.text.isEmpty 
                ? 'Search for books, authors, or categories'
                : 'Try different keywords or filters',
            style: const TextStyle(color: Colors.white70, fontSize: 14),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  void _showCategoryDialog() {
    final categories = _filters['categories'] as List<dynamic>? ?? [];
    
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Select Category', style: TextStyle(color: Colors.white)),
        content: SizedBox(
          width: double.maxFinite,
          child: ListView(
            shrinkWrap: true,
            children: [
              ListTile(
                title: const Text('All Categories', style: TextStyle(color: Colors.white)),
                onTap: () {
                  setState(() {
                    _selectedCategory = '';
                  });
                  Navigator.pop(context);
                },
              ),
              ...categories.map((category) => ListTile(
                title: Text(category['name'], style: const TextStyle(color: Colors.white)),
                onTap: () {
                  setState(() {
                    _selectedCategory = category['name'];
                  });
                  Navigator.pop(context);
                },
              )).toList(),
            ],
          ),
        ),
      ),
    );
  }

  void _showSortDialog() {
    final sortOptions = [
      {'label': 'Relevance', 'value': 'relevance'},
      {'label': 'Title A-Z', 'value': 'title'},
      {'label': 'Author', 'value': 'author'},
      {'label': 'Highest Rated', 'value': 'rating'},
      {'label': 'Newest First', 'value': 'newest'},
      {'label': 'Most Popular', 'value': 'popularity'},
    ];
    
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Sort By', style: TextStyle(color: Colors.white)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: sortOptions.map((option) => ListTile(
            title: Text(option['label']!, style: const TextStyle(color: Colors.white)),
            onTap: () {
              setState(() {
                _selectedSortBy = option['value']!;
              });
              Navigator.pop(context);
            },
          )).toList(),
        ),
      ),
    );
  }

  void _showRatingDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Minimum Rating', style: TextStyle(color: Colors.white)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [0, 1, 2, 3, 4, 5].map((rating) => ListTile(
            title: Text(
              rating == 0 ? 'Any Rating' : '$rating+ Stars',
              style: const TextStyle(color: Colors.white),
            ),
            onTap: () {
              setState(() {
                _minRating = rating.toDouble();
              });
              Navigator.pop(context);
            },
          )).toList(),
        ),
      ),
    );
  }

  void _showAvailabilityDialog() {
    final options = [
      {'label': 'All Books', 'value': 'all'},
      {'label': 'Available Now', 'value': 'available'},
      {'label': 'Currently Borrowed', 'value': 'unavailable'},
    ];
    
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Availability', style: TextStyle(color: Colors.white)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: options.map((option) => ListTile(
            title: Text(option['label']!, style: const TextStyle(color: Colors.white)),
            onTap: () {
              setState(() {
                _selectedAvailability = option['value']!;
              });
              Navigator.pop(context);
            },
          )).toList(),
        ),
      ),
    );
  }
}
