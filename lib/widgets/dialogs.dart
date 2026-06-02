import 'package:flutter/material.dart';

/// Reusable dialog helpers extracted from `DashboardScreen` where
/// `_showErrorDialog` and `_showSuccessDialog` were nearly identical.

void showAppErrorDialog(BuildContext context, String message) {
  showDialog(
    context: context,
    builder: (context) => AlertDialog(
      title: const Text('Error'),
      content: Text(message),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('OK'),
        ),
      ],
    ),
  );
}

void showAppSuccessDialog(
  BuildContext context, {
  required String title,
  required String message,
  VoidCallback? onDismissed,
}) {
  showDialog(
    context: context,
    barrierDismissible: false,
    builder: (context) => AlertDialog(
      title: Text(title),
      content: Text(message),
      actions: [
        TextButton(
          onPressed: () {
            Navigator.pop(context);
            onDismissed?.call();
          },
          child: const Text('OK'),
        ),
      ],
    ),
  );
}
