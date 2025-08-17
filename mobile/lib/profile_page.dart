import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'main.dart';

class ProfilePage extends StatefulWidget {
  final String studentId;

  const ProfilePage({super.key, required this.studentId});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  final String baseUrl = 'http://10.0.2.2:8080';
  Map<String, dynamic> _profile = {};
  Map<String, dynamic> _stats = {};
  List<dynamic> _notifications = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadProfileData();
  }

  Future<void> _loadProfileData() async {
    setState(() {
      _isLoading = true;
    });

    try {
      await Future.wait([
        _loadProfile(),
        _loadStats(),
        _loadNotifications(),
      ]);
    } catch (e) {
      print('Error loading profile data: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadProfile() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/api_profile.php?action=profile&student_id=${widget.studentId}'),
      );
      print('Profile API Response: ${response.body}');
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _profile = data['profile'] ?? {};
          });
          print('Profile loaded: $_profile');
        } else {
          print('Profile API error: ${data['error']}');
        }
      } else {
        print('Profile API HTTP error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error loading profile: $e');
    }
  }

  Future<void> _loadStats() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api/api_profile.php?action=stats&student_id=${widget.studentId}'),
      );
      print('Stats API Response: ${response.body}');
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _stats = data['stats'] ?? {};
          });
          print('Stats loaded: $_stats');
        } else {
          print('Stats API error: ${data['error']}');
        }
      } else {
        print('Stats API HTTP error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error loading stats: $e');
    }
  }

  Future<void> _loadNotifications() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/api_profile_fixed.php?action=notifications&student_id=${widget.studentId}'),
      );
      print('Notifications API Response: ${response.body}');
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _notifications = data['notifications'] ?? [];
          });
          print('Notifications loaded: ${_notifications.length} items');
        } else {
          print('Notifications API error: ${data['error']}');
        }
      } else {
        print('Notifications API HTTP error: ${response.statusCode}');
      }
    } catch (e) {
      print('Error loading notifications: $e');
    }
  }

  Future<void> _logout() async {
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF1A1A2E),
      appBar: AppBar(
        backgroundColor: const Color(0xFF16213E),
        title: const Text('Profile', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            onPressed: _logout,
            icon: const Icon(Icons.logout, color: Colors.white),
            tooltip: 'Logout',
          ),
        ],
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF0F3460)),
            )
          : RefreshIndicator(
              onRefresh: _loadProfileData,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Profile header
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: const Color(0xFF16213E),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: Column(
                        children: [
                          CircleAvatar(
                            radius: 40,
                            backgroundColor: const Color(0xFF0F3460),
                            child: Text(
                              _getInitials(),
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            '${_profile['first_name'] ?? ''} ${_profile['last_name'] ?? ''}',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Text(
                            widget.studentId,
                            style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 16,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            '${_profile['course'] ?? 'Unknown Course'} - Year ${_profile['year_level'] ?? 'N/A'}',
                            style: const TextStyle(
                              color: Colors.white60,
                              fontSize: 14,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Reading stats
                    const Text(
                      'Reading Statistics',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _buildStatCard(
                            'Books Borrowed',
                            _stats['total_borrowed']?.toString() ?? '0',
                            Icons.library_books,
                            Colors.blue,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildStatCard(
                            'Currently Reading',
                            _stats['active_borrowings']?.toString() ?? '0',
                            Icons.menu_book,
                            Colors.green,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: _buildStatCard(
                            'Bookmarks',
                            _stats['total_bookmarks']?.toString() ?? '0',
                            Icons.bookmark,
                            Colors.amber,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildStatCard(
                            'Reviews Written',
                            _stats['total_reviews']?.toString() ?? '0',
                            Icons.rate_review,
                            Colors.purple,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // Recent notifications
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Recent Notifications',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        if (_notifications.isNotEmpty)
                          TextButton(
                            onPressed: () {
                              // Mark all as read
                              _markAllNotificationsRead();
                            },
                            child: const Text(
                              'Mark all read',
                              style: TextStyle(color: Color(0xFF0F3460)),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    _notifications.isEmpty
                        ? Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(20),
                            decoration: BoxDecoration(
                              color: const Color(0xFF16213E),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Column(
                              children: [
                                Icon(Icons.notifications_off, size: 48, color: Colors.white54),
                                SizedBox(height: 8),
                                Text(
                                  'No notifications',
                                  style: TextStyle(color: Colors.white70),
                                ),
                              ],
                            ),
                          )
                        : Column(
                            children: _notifications.take(5).map((notification) {
                              return _buildNotificationCard(notification);
                            }).toList(),
                          ),
                    const SizedBox(height: 24),

                    // Account info
                    const Text(
                      'Account Information',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _buildInfoCard('Email', _profile['email'] ?? 'Not provided'),
                    _buildInfoCard('Phone', _profile['phone'] ?? 'Not provided'),
                    _buildInfoCard('Address', _profile['address'] ?? 'Not provided'),
                  ],
                ),
              ),
            ),
    );
  }

  String _getInitials() {
    final firstName = _profile['first_name'] ?? '';
    final lastName = _profile['last_name'] ?? '';
    return '${firstName.isNotEmpty ? firstName[0] : ''}${lastName.isNotEmpty ? lastName[0] : ''}'.toUpperCase();
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF16213E),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 32),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            title,
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 12,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationCard(Map<String, dynamic> notification) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF16213E),
        borderRadius: BorderRadius.circular(8),
        border: notification['is_read'] == false
            ? Border.all(color: const Color(0xFF0F3460), width: 1)
            : null,
      ),
      child: Row(
        children: [
          Icon(
            _getNotificationIcon(notification['type']),
            color: _getNotificationColor(notification['type']),
            size: 20,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  notification['message'] ?? '',
                  style: const TextStyle(color: Colors.white, fontSize: 14),
                ),
                const SizedBox(height: 4),
                Text(
                  notification['created_at'] ?? '',
                  style: const TextStyle(color: Colors.white54, fontSize: 12),
                ),
              ],
            ),
          ),
          if (notification['is_read'] == false)
            Container(
              width: 8,
              height: 8,
              decoration: const BoxDecoration(
                color: Color(0xFF0F3460),
                shape: BoxShape.circle,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildInfoCard(String title, String value) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF16213E),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          Text(
            '$title:',
            style: const TextStyle(
              color: Colors.white70,
              fontSize: 14,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(color: Colors.white, fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }

  IconData _getNotificationIcon(String? type) {
    switch (type) {
      case 'due_reminder':
        return Icons.schedule;
      case 'overdue':
        return Icons.warning;
      case 'return_confirmation':
        return Icons.check_circle;
      case 'recommendation':
        return Icons.recommend;
      default:
        return Icons.notifications;
    }
  }

  Color _getNotificationColor(String? type) {
    switch (type) {
      case 'due_reminder':
        return Colors.orange;
      case 'overdue':
        return Colors.red;
      case 'return_confirmation':
        return Colors.green;
      case 'recommendation':
        return Colors.blue;
      default:
        return Colors.white70;
    }
  }

  Future<void> _markAllNotificationsRead() async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api_profile.php'),
        body: {
          'action': 'mark_notifications_read',
          'student_id': widget.studentId,
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          _loadNotifications(); // Refresh notifications
        }
      }
    } catch (e) {
      print('Error marking notifications as read: $e');
    }
  }
}
