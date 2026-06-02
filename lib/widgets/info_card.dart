import 'package:flutter/material.dart';

/// A small info banner used at the bottom of both [LoginScreen] and
/// [DashboardScreen].  Previously duplicated inline in each screen's
/// build method.
class InfoCard extends StatelessWidget {
  const InfoCard({
    super.key,
    required this.message,
    this.color = Colors.amber,
  });

  final String message;

  /// The base [MaterialColor] used to derive background, border and
  /// text colours.
  final MaterialColor color;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      color: color.shade50,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Icon(
              Icons.info_outline,
              color: color.shade700,
              size: 24,
            ),
            const SizedBox(height: 8),
            Text(
              message,
              style: TextStyle(
                color: color.shade700,
                fontSize: 12,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
