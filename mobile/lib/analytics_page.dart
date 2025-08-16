import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class AnalyticsPage extends StatefulWidget {
  final String studentId;

  const AnalyticsPage({super.key, required this.studentId});

  @override
  State<AnalyticsPage> createState() => _AnalyticsPageState();
}

class _AnalyticsPageState extends State<AnalyticsPage> {
  final String baseUrl = 'http://10.0.2.2:8080';
  
  Map<String, dynamic> _streak = {};
  List<dynamic> _achievements = [];
  List<dynamic> _goals = [];
  Map<String, dynamic> _analytics = {};
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadAnalyticsData();
  }

  Future<void> _loadAnalyticsData() async {
    setState(() {
      _isLoading = true;
    });

    try {
      await Future.wait([
        _loadReadingStreak(),
        _loadAchievements(),
        _loadReadingGoals(),
        _loadReadingAnalytics(),
      ]);
    } catch (e) {
      print('Error loading analytics: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadReadingStreak() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_engagement.php?action=reading_streak&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _streak = data['streak'] ?? {};
          });
        }
      }
    } catch (e) {
      print('Error loading reading streak: $e');
    }
  }

  Future<void> _loadAchievements() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_engagement.php?action=achievements&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _achievements = data['achievements'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading achievements: $e');
    }
  }

  Future<void> _loadReadingGoals() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_engagement.php?action=reading_goals&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _goals = data['goals'] ?? [];
          });
        }
      }
    } catch (e) {
      print('Error loading reading goals: $e');
    }
  }

  Future<void> _loadReadingAnalytics() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_engagement.php?action=analytics&student_id=${widget.studentId}'),
      );
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _analytics = data['analytics'] ?? {};
          });
        }
      }
    } catch (e) {
      print('Error loading analytics: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1A2E),
      appBar: AppBar(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Reading Analytics', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF0F3460)),
            )
          : RefreshIndicator(
              onRefresh: _loadAnalyticsData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildReadingStreak(),
                    const SizedBox(height: 24),
                    _buildReadingGoals(),
                    const SizedBox(height: 24),
                    _buildAchievements(),
                    const SizedBox(height: 24),
                    _buildReadingStats(),
                    const SizedBox(height: 24),
                    _buildCategoryDistribution(),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildReadingStreak() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF16213E),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.local_fire_department, color: Colors.orange, size: 24),
              const SizedBox(width: 8),
              const Text(
                'Reading Streak',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${_streak['current_streak'] ?? 0}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 32,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Text(
                      'Current Streak',
                      style: TextStyle(color: Colors.white70, fontSize: 14),
                    ),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${_streak['longest_streak'] ?? 0}',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 32,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Text(
                      'Best Streak',
                      style: TextStyle(color: Colors.white70, fontSize: 14),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          LinearProgressIndicator(
            value: (_streak['streak_score'] ?? 0) / 100,
            backgroundColor: Colors.white24,
            valueColor: const AlwaysStoppedAnimation<Color>(Colors.orange),
          ),
          const SizedBox(height: 8),
          Text(
            'Streak Score: ${_streak['streak_score']?.toStringAsFixed(1) ?? '0.0'}%',
            style: const TextStyle(color: Colors.white70, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _buildReadingGoals() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Reading Goals',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),
        ..._goals.map((goal) => Container(
          width: double.infinity,
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: const Color(0xFF16213E),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      goal['title'] ?? '',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: goal['status'] == 'completed' ? Colors.green : const Color(0xFF0F3460),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      goal['status'] == 'completed' ? 'Completed' : 'Active',
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                goal['description'] ?? '',
                style: const TextStyle(color: Colors.white70, fontSize: 14),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Text(
                    '${goal['current'] ?? 0} / ${goal['target'] ?? 0}',
                    style: const TextStyle(color: Colors.white, fontSize: 14),
                  ),
                  const Spacer(),
                  Text(
                    '${goal['progress']?.toStringAsFixed(1) ?? '0.0'}%',
                    style: const TextStyle(color: Colors.white70, fontSize: 12),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              LinearProgressIndicator(
                value: (goal['progress'] ?? 0) / 100,
                backgroundColor: Colors.white24,
                valueColor: AlwaysStoppedAnimation<Color>(
                  goal['status'] == 'completed' ? Colors.green : const Color(0xFF0F3460),
                ),
              ),
            ],
          ),
        )).toList(),
      ],
    );
  }

  Widget _buildAchievements() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Achievements',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.2,
          ),
          itemCount: _achievements.length,
          itemBuilder: (context, index) {
            final achievement = _achievements[index];
            final isUnlocked = achievement['unlocked'] ?? false;
            
            return Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isUnlocked ? const Color(0xFF16213E) : const Color(0xFF16213E).withOpacity(0.5),
                borderRadius: BorderRadius.circular(12),
                border: isUnlocked 
                    ? Border.all(color: const Color(0xFF0F3460), width: 2)
                    : null,
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    achievement['icon'] ?? '🏆',
                    style: TextStyle(
                      fontSize: 32,
                      color: isUnlocked ? null : Colors.white30,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    achievement['title'] ?? '',
                    style: TextStyle(
                      color: isUnlocked ? Colors.white : Colors.white54,
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    achievement['description'] ?? '',
                    style: TextStyle(
                      color: isUnlocked ? Colors.white70 : Colors.white38,
                      fontSize: 10,
                    ),
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (!isUnlocked) ...[
                    const SizedBox(height: 8),
                    LinearProgressIndicator(
                      value: (achievement['progress'] ?? 0) / 100,
                      backgroundColor: Colors.white24,
                      valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF0F3460)),
                    ),
                  ],
                ],
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildReadingStats() {
    final monthlyTrend = _analytics['monthly_trend'] as List<dynamic>? ?? [];
    
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF16213E),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Reading Statistics',
            style: TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _buildStatItem(
                  'Total Books',
                  '${_analytics['total_books'] ?? 0}',
                  Icons.library_books,
                  Colors.blue,
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: _buildStatItem(
                  'Avg. Reading Time',
                  '${_analytics['avg_reading_days']?.toStringAsFixed(1) ?? '0.0'} days',
                  Icons.schedule,
                  Colors.green,
                ),
              ),
            ],
          ),
          if (monthlyTrend.isNotEmpty) ...[
            const SizedBox(height: 16),
            const Text(
              'Monthly Trend',
              style: TextStyle(color: Colors.white70, fontSize: 14),
            ),
            const SizedBox(height: 8),
            SizedBox(
              height: 60,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                itemCount: monthlyTrend.length,
                itemBuilder: (context, index) {
                  final month = monthlyTrend[index];
                  return Container(
                    width: 80,
                    margin: const EdgeInsets.only(right: 8),
                    child: Column(
                      children: [
                        Container(
                          height: 30,
                          width: 30,
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F3460),
                            borderRadius: BorderRadius.circular(15),
                          ),
                          child: Center(
                            child: Text(
                              '${month['books_borrowed'] ?? 0}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          month['month']?.substring(5) ?? '',
                          style: const TextStyle(
                            color: Colors.white54,
                            fontSize: 10,
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildStatItem(String label, String value, IconData icon, Color color) {
    return Column(
      children: [
        Icon(icon, color: color, size: 24),
        const SizedBox(height: 8),
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        Text(
          label,
          style: const TextStyle(
            color: Colors.white70,
            fontSize: 12,
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );
  }

  Widget _buildCategoryDistribution() {
    final categoryData = _analytics['category_distribution'] as List<dynamic>? ?? [];
    
    if (categoryData.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Reading Categories',
          style: TextStyle(
            color: Colors.white,
            fontSize: 18,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),
        ...categoryData.take(5).map((category) => Container(
          margin: const EdgeInsets.only(bottom: 8),
          child: Row(
            children: [
              Container(
                width: 12,
                height: 12,
                decoration: BoxDecoration(
                  color: Color(int.parse(category['color']?.replaceAll('#', '0xFF') ?? '0xFF0F3460')),
                  shape: BoxShape.circle,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  category['category'] ?? '',
                  style: const TextStyle(color: Colors.white, fontSize: 14),
                ),
              ),
              Text(
                '${category['book_count'] ?? 0} books',
                style: const TextStyle(color: Colors.white70, fontSize: 12),
              ),
              const SizedBox(width: 8),
              Text(
                '${category['percentage']?.toStringAsFixed(1) ?? '0.0'}%',
                style: const TextStyle(color: Colors.white54, fontSize: 12),
              ),
            ],
          ),
        )).toList(),
      ],
    );
  }
}
