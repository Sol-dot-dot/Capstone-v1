import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'landing_page.dart';
import 'borrowing_page.dart';
import 'my_books_page.dart';

// Backend base URL for API calls
// Android Emulator: host machine is 10.0.2.2; our PHP server uses port 8080
const String kBaseUrl = 'http://10.0.2.2:8080';

void main() {
  runApp(const CapstoneLoginApp());
}

class CapstoneLoginApp extends StatelessWidget {
  const CapstoneLoginApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Capstone Login',
      theme: ThemeData(
        primarySwatch: Colors.blue,
      ),
      home: const LoginPage(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _studentIdController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  String? _message;

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _message = null;
    });

    final backendUrl = "$kBaseUrl/login.php";

    try {
      final response = await http.post(
        Uri.parse('$kBaseUrl/auth/login.php'),
        body: {
          'student_id': _studentIdController.text.trim(),
          'password': _passwordController.text,
        },
      );
      // Debug logging
      // ignore: avoid_print
      print('LOGIN status: ${response.statusCode}');
      // ignore: avoid_print
      print('LOGIN body: ${response.body}');

      if (response.statusCode != 200) {
        setState(() {
          _message = 'HTTP ${response.statusCode}: ${response.body}';
        });
        return;
      }

      Map<String, dynamic> data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        setState(() {
          _message = 'Server returned non-JSON. See logs.';
        });
        return;
      }

      if (data['success'] == true) {
        // Navigate to landing page on successful login
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (context) => LandingPage(
              studentId: _studentIdController.text.trim().toUpperCase(),
            ),
          ),
        );
      } else {
        setState(() {
          _message = data['error'] ?? 'Login failed';
        });
      }
    } catch (e) {
      setState(() {
        _message = 'Network error: $e';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              TextFormField(
                controller: _studentIdController,
                decoration: const InputDecoration(labelText: 'Student ID (e.g., C22-0044)'),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Enter student ID';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _passwordController,
                obscureText: true,
                decoration: const InputDecoration(labelText: 'Password'),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Enter password';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 24),
              _isLoading
                  ? const CircularProgressIndicator()
                  : ElevatedButton(
                      onPressed: _login,
                      child: const Text('Login'),
                    ),
              TextButton(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(builder: (_) => const RegisterPage()),
                  );
                },
                child: const Text('Create an account'),
              ),
              if (_message != null) ...[
                const SizedBox(height: 16),
                Text(
                  _message!,
                  style: TextStyle(
                    color: _message == 'Login successful'
                        ? Colors.green
                        : Colors.red,
                  ),
                ),
              ]
            ],
          ),
        ),
      ),
    );
  }
}

// ---------------- Register Page ----------------
class RegisterPage extends StatefulWidget {
  const RegisterPage({super.key});

  @override
  State<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _studentIdController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  int _currentStep = 0;
  bool _isLoading = false;
  String? _message;
  String? _verificationCode;

  Future<void> _sendCode() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isLoading = true;
      _message = null;
    });

    final backendUrl = "$kBaseUrl/send_code.php";

    try {
      final response = await http.post(Uri.parse(backendUrl), body: {
        'student_id': _studentIdController.text.trim().toUpperCase(),
        'email': _emailController.text.trim().toLowerCase(),
      });
      // Debug logging
      // ignore: avoid_print
      print('SEND_CODE status: ${response.statusCode}');
      // ignore: avoid_print
      print('SEND_CODE body: ${response.body}');

      if (response.statusCode != 200) {
        setState(() {
          _message = 'HTTP ${response.statusCode}: ${response.body}';
        });
        return;
      }

      Map<String, dynamic> data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        setState(() {
          _message = 'Server returned non-JSON. See logs.';
        });
        return;
      }

      if (data['success'] == true) {
        setState(() {
          _currentStep = 1;
          _message = 'Verification code sent to your email';
        });
      } else {
        setState(() {
          _message = data['error'] ?? 'Failed to send code';
        });
      }
    } catch (e) {
      setState(() {
        _message = 'Network error: $e';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _verifyCode() async {
    if (_verificationCode == null || _verificationCode!.length != 6) {
      setState(() {
        _message = 'Please enter a 6-digit code';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _message = null;
    });

    final backendUrl = "$kBaseUrl/verify_code.php";

    try {
      final response = await http.post(Uri.parse(backendUrl), body: {
        'student_id': _studentIdController.text.trim().toUpperCase(),
        'email': _emailController.text.trim().toLowerCase(),
        'code': _verificationCode!,
      });
      // Debug logging
      // ignore: avoid_print
      print('VERIFY_CODE status: ${response.statusCode}');
      // ignore: avoid_print
      print('VERIFY_CODE body: ${response.body}');

      if (response.statusCode != 200) {
        setState(() {
          _message = 'HTTP ${response.statusCode}: ${response.body}';
        });
        return;
      }

      Map<String, dynamic> data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        setState(() {
          _message = 'Server returned non-JSON. See logs.';
        });
        return;
      }

      if (data['success'] == true) {
        setState(() {
          _currentStep = 2;
          _message = 'Email verified! Set your password';
        });
      } else {
        setState(() {
          _message = data['error'] ?? 'Invalid or expired code';
        });
      }
    } catch (e) {
      setState(() {
        _message = 'Network error: $e';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _register() async {
    if (!_passwordController.text.contains(RegExp(r'^(?=.*[A-Z])(?=.*[0-9]).{8,}$'))) {
      setState(() {
        _message = 'Password must be 8+ chars with 1 uppercase and 1 number';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _message = null;
    });

    final backendUrl = "$kBaseUrl/register_student.php";

    try {
      final response = await http.post(Uri.parse(backendUrl), body: {
        'student_id': _studentIdController.text.trim().toUpperCase(),
        'email': _emailController.text.trim().toLowerCase(),
        'password': _passwordController.text,
      });
      // Debug logging
      // ignore: avoid_print
      print('REGISTER status: ${response.statusCode}');
      // ignore: avoid_print
      print('REGISTER body: ${response.body}');

      if (response.statusCode != 200) {
        setState(() {
          _message = 'HTTP ${response.statusCode}: ${response.body}';
        });
        return;
      }

      Map<String, dynamic> data;
      try {
        data = jsonDecode(response.body);
      } catch (_) {
        setState(() {
          _message = 'Server returned non-JSON. See logs.';
        });
        return;
      }

      if (data['success'] == true) {
        setState(() {
          _message = 'Registration successful! You can now log in.';
        });
        // Navigate back to login after 2 seconds
        Future.delayed(Duration(seconds: 2), () {
          Navigator.pop(context);
        });
      } else {
        setState(() {
          _message = data['error'] ?? 'Registration failed';
        });
      }
    } catch (e) {
      setState(() {
        _message = 'Error connecting to server';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Register')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (_currentStep == 0) ...[
                // Step 1: Student ID and Email
                TextFormField(
                  controller: _studentIdController,
                  decoration: const InputDecoration(labelText: 'Student ID (e.g., C22-0044)'),
                  validator: (value) {
                    if (value == null || value.isEmpty) return 'Enter student ID';
                    return null;
                  },
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(labelText: 'School Email (@my.smciligan.edu.ph)'),
                  validator: (value) {
                    if (value == null || value.isEmpty) return 'Enter email';
                    if (!value.endsWith('@my.smciligan.edu.ph')) return 'Must be SMC email';
                    return null;
                  },
                ),
                const SizedBox(height: 24),
                _isLoading
                    ? const CircularProgressIndicator()
                    : ElevatedButton(
                        onPressed: _sendCode,
                        child: const Text('Send Verification Code'),
                      ),
              ] else if (_currentStep == 1) ...[
                // Step 2: Verification Code
                Text('Enter the 6-digit code sent to ${_emailController.text}'),
                const SizedBox(height: 16),
                TextFormField(
                  onChanged: (value) => _verificationCode = value,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  decoration: const InputDecoration(labelText: 'Verification Code'),
                ),
                const SizedBox(height: 24),
                _isLoading
                    ? const CircularProgressIndicator()
                    : ElevatedButton(
                        onPressed: _verifyCode,
                        child: const Text('Verify Code'),
                      ),
              ] else if (_currentStep == 2) ...[
                // Step 3: Password
                Text('Set your password for ${_studentIdController.text}'),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Password',
                    helperText: '8+ chars, 1 uppercase, 1 number',
                  ),
                ),
                const SizedBox(height: 24),
                _isLoading
                    ? const CircularProgressIndicator()
                    : ElevatedButton(
                        onPressed: _register,
                        child: const Text('Complete Registration'),
                      ),
              ],
              if (_message != null) ...[
                const SizedBox(height: 16),
                Text(
                  _message!,
                  style: TextStyle(
                    color: _message!.contains('successful') || _message!.contains('sent') || _message!.contains('verified')
                        ? Colors.green
                        : Colors.red,
                  ),
                ),
              ]
            ],
          ),
        ),
      ),
    );
  }
}
