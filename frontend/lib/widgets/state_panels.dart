import 'package:flutter/material.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';

class LoadingPanel extends StatelessWidget {
  const LoadingPanel({super.key, this.label = 'Loading clearance data'});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: label,
      child: Column(
        children: List.generate(
          3,
          (index) => Container(
            height: index == 0 ? 108 : 78,
            margin: const EdgeInsets.only(bottom: 12),
            decoration: BoxDecoration(
              color: index.isEven
                  ? const Color(0xFFE6ECE8)
                  : const Color(0xFFEDF1EE),
              borderRadius: BorderRadius.circular(16),
            ),
          ),
        ),
      ),
    );
  }
}

class ErrorPanel extends StatelessWidget {
  const ErrorPanel({
    super.key,
    required this.error,
    required this.onRetry,
    this.onReturnToRoles,
  });

  final Object error;
  final VoidCallback onRetry;
  final VoidCallback? onReturnToRoles;

  @override
  Widget build(BuildContext context) {
    final apiError = error is ApiException ? error as ApiException : null;
    final unauthorized = apiError?.isUnauthorized ?? false;
    final message =
        apiError?.message ?? 'Something went wrong while loading this view.';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              unauthorized ? Icons.gpp_bad_outlined : Icons.cloud_off_outlined,
              color: Theme.of(context).colorScheme.error,
              size: 30,
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    unauthorized
                        ? 'Access not permitted'
                        : 'Unable to load this view',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  const SizedBox(height: 8),
                  Text(message),
                  if (apiError?.correlationId != null) ...[
                    const SizedBox(height: 8),
                    SelectableText(
                      'Reference: ${apiError!.correlationId}',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ],
                  const SizedBox(height: 18),
                  Wrap(
                    spacing: 10,
                    runSpacing: 10,
                    children: [
                      FilledButton.icon(
                        onPressed: onRetry,
                        icon: const Icon(Icons.refresh_rounded),
                        label: const Text('Try again'),
                      ),
                      if (unauthorized && onReturnToRoles != null)
                        OutlinedButton(
                          onPressed: onReturnToRoles,
                          child: const Text('Change role'),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class EmptyPanel extends StatelessWidget {
  const EmptyPanel({
    super.key,
    required this.icon,
    required this.title,
    required this.message,
    this.action,
  });

  final IconData icon;
  final String title;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          children: [
            Icon(icon, size: 36, color: Theme.of(context).colorScheme.primary),
            const SizedBox(height: 14),
            Text(title, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center),
            if (action != null) ...[const SizedBox(height: 20), action!],
          ],
        ),
      ),
    );
  }
}

class StatusLabel extends StatelessWidget {
  const StatusLabel(this.status, {super.key});

  final String status;

  @override
  Widget build(BuildContext context) {
    final (label, color) = switch (status) {
      'completed' ||
      'approved' ||
      'delivered' => ('Complete', const Color(0xFF276749)),
      'deficiency' ||
      'human_action_required' => ('Needs action', const Color(0xFF9A3412)),
      'awaiting_re_review' => ('Awaiting review', const Color(0xFF7C5B13)),
      _ => ('Pending', const Color(0xFF42534B)),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
          fontSize: 12,
        ),
      ),
    );
  }
}
