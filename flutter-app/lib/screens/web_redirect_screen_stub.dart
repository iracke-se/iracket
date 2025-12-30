import 'package:flutter/material.dart';

// Stub implementation for non-web platforms
class WebRedirectScreen extends StatelessWidget {
  const WebRedirectScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: Text('This screen is only available on web'),
      ),
    );
  }
}
