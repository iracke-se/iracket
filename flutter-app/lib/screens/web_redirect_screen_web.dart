import 'package:flutter/material.dart';
import '../config/environment.dart';
// ignore: avoid_web_libraries_in_flutter
import 'dart:html' as html;

class WebRedirectScreen extends StatefulWidget {
  const WebRedirectScreen({super.key});

  @override
  State<WebRedirectScreen> createState() => _WebRedirectScreenState();
}

class _WebRedirectScreenState extends State<WebRedirectScreen> {
  @override
  void initState() {
    super.initState();
    _redirectToLaravel();
  }

  void _redirectToLaravel() {
    // Redirect to Laravel app after 1 second
    Future.delayed(const Duration(seconds: 1), () {
      html.window.location.href = Environment.laravelBaseUrl;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const CircularProgressIndicator(),
            const SizedBox(height: 24),
            Text(
              'Redirecting to iRacket...',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 16),
            Text(
              'If you are not redirected automatically,',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            TextButton(
              onPressed: () {
                html.window.location.href = Environment.laravelBaseUrl;
              },
              child: const Text('click here'),
            ),
          ],
        ),
      ),
    );
  }
}
