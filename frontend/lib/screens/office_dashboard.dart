import 'package:flutter/material.dart';
import 'package:student_clearance_frontend/api/clearance_api.dart';
import 'package:student_clearance_frontend/models/session.dart';
import 'package:student_clearance_frontend/widgets/app_shell.dart';
import 'package:student_clearance_frontend/widgets/state_panels.dart';

class OfficeDashboard extends StatefulWidget {
  const OfficeDashboard({
    super.key,
    required this.api,
    required this.session,
    required this.onSignOut,
  });

  final ClearanceApi api;
  final DemoSession session;
  final VoidCallback onSignOut;

  @override
  State<OfficeDashboard> createState() => _OfficeDashboardState();
}

class _OfficeDashboardState extends State<OfficeDashboard> {
  List<Map<String, dynamic>> _tasks = const [];
  Object? _error;
  bool _loading = true;
  int? _busyTask;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      session: widget.session,
      title: '${widget.session.user.officeName} review queue',
      subtitle: 'Record a deficiency or approve an eligible task assigned to this office.',
      onSignOut: widget.onSignOut,
      actions: [
        IconButton(
          tooltip: 'Refresh tasks',
          onPressed: _loading ? null : _load,
          icon: const Icon(Icons.refresh_rounded),
        ),
      ],
      child: _body(),
    );
  }

  Widget _body() {
    if (_loading) {
      return const LoadingPanel(label: 'Loading assigned office tasks');
    }
    if (_error != null) {
      return ErrorPanel(
        error: _error!,
        onRetry: _load,
        onReturnToRoles: widget.onSignOut,
      );
    }
    if (_tasks.isEmpty) {
      return const EmptyPanel(
        icon: Icons.inbox_outlined,
        title: 'No assigned tasks',
        message: 'This office has no student clearance task to review.',
      );
    }
    return Column(children: _tasks.map(_taskCard).toList());
  }

  Widget _taskCard(Map<String, dynamic> task) {
    final student = task['student'] as Map<String, dynamic>;
    final deficiencies = (task['deficiencies'] as List<dynamic>)
        .map((item) => item as Map<String, dynamic>)
        .toList();
    final latest = deficiencies.isEmpty ? null : deficiencies.last;
    final status = task['status'] as String;
    final canApprove = status == 'pending' || status == 'awaiting_re_review';
    final canReport = status == 'pending' || status == 'awaiting_re_review';
    final busy = _busyTask == task['id'];

    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(22),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Wrap(
                alignment: WrapAlignment.spaceBetween,
                crossAxisAlignment: WrapCrossAlignment.center,
                spacing: 16,
                runSpacing: 10,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        student['name'] as String,
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 4),
                      Text('Clearance task ${task['id']}'),
                    ],
                  ),
                  StatusLabel(status),
                ],
              ),
              if (latest != null) ...[
                const SizedBox(height: 18),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF2F5F3),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        latest['status'] == 'resolved'
                            ? 'Student response received'
                            : 'Open deficiency',
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 6),
                      Text(latest['description'] as String),
                      if (latest['resolution_note'] != null) ...[
                        const SizedBox(height: 10),
                        Text('Resolution: ${latest['resolution_note']}'),
                      ],
                    ],
                  ),
                ),
              ],
              if (canApprove || canReport) ...[
                const SizedBox(height: 18),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    if (canApprove)
                      FilledButton.icon(
                        onPressed: busy
                            ? null
                            : () => _approve(task['id'] as int),
                        icon: const Icon(Icons.check_rounded),
                        label: Text(busy ? 'Saving...' : 'Approve task'),
                      ),
                    if (canReport)
                      OutlinedButton.icon(
                        onPressed: busy
                            ? null
                            : () => _showDeficiency(task['id'] as int),
                        icon: const Icon(Icons.report_problem_outlined),
                        label: const Text('Report deficiency'),
                      ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final tasks = await widget.api.officeTasks(widget.session.token);
      if (mounted) setState(() => _tasks = tasks);
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _approve(int taskId) async {
    setState(() => _busyTask = taskId);
    try {
      await widget.api.approveTask(widget.session.token, taskId);
      await _load();
    } catch (error) {
      if (mounted) setState(() => _error = error);
    } finally {
      if (mounted) setState(() => _busyTask = null);
    }
  }

  Future<void> _showDeficiency(int taskId) async {
    final controller = TextEditingController();
    String? errorText;
    final submitted = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('Report a deficiency'),
          content: SizedBox(
            width: 480,
            child: TextField(
              controller: controller,
              minLines: 3,
              maxLines: 6,
              decoration: InputDecoration(
                labelText: 'Deficiency description',
                helperText:
                    'State the specific action the student must complete.',
                errorText: errorText,
              ),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () {
                if (controller.text.trim().length < 5) {
                  setDialogState(
                    () => errorText = 'Enter at least 5 characters.',
                  );
                } else {
                  Navigator.pop(dialogContext, true);
                }
              },
              child: const Text('Record deficiency'),
            ),
          ],
        ),
      ),
    );
    if (submitted == true) {
      setState(() => _busyTask = taskId);
      try {
        await widget.api.reportDeficiency(
          widget.session.token,
          taskId,
          controller.text.trim(),
        );
        await _load();
      } catch (error) {
        if (mounted) setState(() => _error = error);
      } finally {
        if (mounted) setState(() => _busyTask = null);
      }
    }
    controller.dispose();
  }
}
