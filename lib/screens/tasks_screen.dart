import 'package:flutter/material.dart';
import '../services/api_service.dart';

class TasksScreen extends StatefulWidget {
  final String sessionId;

  const TasksScreen({super.key, required this.sessionId});

  @override
  State<TasksScreen> createState() => _TasksScreenState();
}

class _TasksScreenState extends State<TasksScreen> {
  bool _loading = true;
  String? _error;
  List<Map<String, dynamic>> _tasks = const [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final res = await ApiService.getMyTasks(widget.sessionId);
    if (!mounted) return;

    if (res['success'] == true) {
      final rows = (res['data'] as List?)?.cast<Map>() ?? const [];
      setState(() {
        _tasks = rows.map((e) => Map<String, dynamic>.from(e)).toList();
        _loading = false;
      });
    } else {
      setState(() {
        _error = res['message']?.toString() ?? 'Failed to load tasks';
        _loading = false;
      });
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'pending':
        return Colors.grey;
      case 'in_progress':
        return Colors.blue;
      case 'completed':
        return Colors.green;
      case 'blocked':
        return Colors.red;
      case 'expired':
        return Colors.orange;
      default:
        return Colors.black54;
    }
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'pending':
        return 'Pending';
      case 'in_progress':
        return 'In Progress';
      case 'completed':
        return 'Completed';
      case 'blocked':
        return 'Blocked';
      case 'expired':
        return 'Expired';
      default:
        return status;
    }
  }

  Future<void> _startTask(int taskId) async {
    final res = await ApiService.updateTaskStatus(
      sessionId: widget.sessionId,
      taskId: taskId,
      action: 'start',
    );
    if (!mounted) return;
    if (res['success'] == true) {
      await _load();
    } else {
      _showMessage(res['message']?.toString() ?? 'Failed to start task');
    }
  }

  Future<void> _completeTask(int taskId) async {
    final res = await ApiService.updateTaskStatus(
      sessionId: widget.sessionId,
      taskId: taskId,
      action: 'complete',
    );
    if (!mounted) return;
    if (res['success'] == true) {
      await _load();
    } else {
      _showMessage(res['message']?.toString() ?? 'Failed to complete task');
    }
  }

  Future<void> _blockTask(int taskId) async {
    final controller = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Block task'),
        content: TextField(
          controller: controller,
          decoration: const InputDecoration(
            labelText: 'Reason',
            hintText: 'Describe the blocker',
          ),
          minLines: 2,
          maxLines: 5,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, controller.text.trim()),
            child: const Text('Block'),
          ),
        ],
      ),
    );

    if (reason == null) return;
    if (reason.isEmpty) {
      _showMessage('Reason is required to block a task');
      return;
    }

    final res = await ApiService.updateTaskStatus(
      sessionId: widget.sessionId,
      taskId: taskId,
      action: 'block',
      reason: reason,
    );
    if (!mounted) return;
    if (res['success'] == true) {
      await _load();
    } else {
      _showMessage(res['message']?.toString() ?? 'Failed to block task');
    }
  }

  void _showMessage(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Tasks'),
        actions: [
          IconButton(
            onPressed: _load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          _error!,
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 12),
                        ElevatedButton(
                          onPressed: _load,
                          child: const Text('Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : _tasks.isEmpty
                  ? const Center(child: Text('No tasks assigned'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(12),
                      itemCount: _tasks.length,
                      itemBuilder: (context, index) {
                        final t = _tasks[index];
                        final id = int.tryParse(t['task_id']?.toString() ?? '') ?? 0;
                        final title = t['title']?.toString() ?? '';
                        final desc = t['description']?.toString() ?? '';
                        final status = t['status']?.toString() ?? 'pending'; // real status used for actions
                        final effectiveStatus = t['effective_status']?.toString();
                        final displayStatus = (effectiveStatus != null && effectiveStatus.isNotEmpty)
                            ? effectiveStatus
                            : status;
                        final due = t['due_date']?.toString();
                        final blockReason = t['block_reason']?.toString();

                        final statusColor = _statusColor(displayStatus);
                        final canStart = status == 'pending' || status == 'blocked';
                        final canComplete = status == 'in_progress';
                        final canBlock = status == 'pending' || status == 'in_progress';

                        return Card(
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        title,
                                        style: const TextStyle(
                                          fontSize: 16,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                      decoration: BoxDecoration(
                                        color: statusColor.withOpacity(0.12),
                                        borderRadius: BorderRadius.circular(999),
                                      ),
                                      child: Text(
                                        _statusLabel(displayStatus),
                                        style: TextStyle(
                                          color: statusColor,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                if (due != null && due.isNotEmpty)
                                  Text(
                                    'Due: $due',
                                    style: TextStyle(color: Colors.grey.shade700),
                                  ),
                                if (desc.isNotEmpty) ...[
                                  const SizedBox(height: 8),
                                  Text(desc),
                                ],
                                if (status == 'blocked' && (blockReason ?? '').isNotEmpty) ...[
                                  const SizedBox(height: 10),
                                  Container(
                                    width: double.infinity,
                                    padding: const EdgeInsets.all(10),
                                    decoration: BoxDecoration(
                                      color: Colors.red.withOpacity(0.08),
                                      borderRadius: BorderRadius.circular(8),
                                      border: Border.all(color: Colors.red.withOpacity(0.2)),
                                    ),
                                    child: Text('Block reason: $blockReason'),
                                  ),
                                ],
                                const SizedBox(height: 10),
                                Wrap(
                                  spacing: 8,
                                  runSpacing: 8,
                                  children: [
                                    ElevatedButton(
                                      onPressed: canStart && id > 0 ? () => _startTask(id) : null,
                                      child: const Text('Start'),
                                    ),
                                    ElevatedButton(
                                      onPressed: canComplete && id > 0 ? () => _completeTask(id) : null,
                                      style: ElevatedButton.styleFrom(backgroundColor: Colors.green),
                                      child: const Text('Complete'),
                                    ),
                                    OutlinedButton(
                                      onPressed: canBlock && id > 0 ? () => _blockTask(id) : null,
                                      child: const Text('Block'),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
    );
  }
}

