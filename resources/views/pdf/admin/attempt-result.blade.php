@php
    $formatDate = fn ($value): string => $value ? $value->format('M d, Y h:i A') : 'Not recorded';
    $formatLabel = fn (?string $value): string => $value ? str_replace(' ', ' ', ucwords(str_replace('_', ' ', $value))) : 'Not recorded';
    $formatValue = function (mixed $value): string {
        if ($value === null || $value === '') {
            return 'Not provided';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'Not provided';
    };
    $resultLabel = $attempt['passed'] === null ? 'Pending' : ($attempt['passed'] ? 'Passed' : 'Failed');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $test['title'] }} Result Report</title>
    <style>
        * { box-sizing: border-box; }
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            margin: 28px;
        }
        h1, h2, h3 { margin: 0; }
        h1 { font-size: 22px; }
        h2 {
            border-bottom: 1px solid #d1d5db;
            font-size: 14px;
            margin: 22px 0 10px;
            padding-bottom: 6px;
            text-transform: uppercase;
        }
        .muted { color: #6b7280; }
        .header {
            border-bottom: 2px solid #111827;
            margin-bottom: 18px;
            padding-bottom: 12px;
        }
        .meta {
            color: #4b5563;
            margin-top: 6px;
        }
        table {
            border-collapse: collapse;
            margin-bottom: 12px;
            width: 100%;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            color: #374151;
            font-size: 10px;
            text-transform: uppercase;
        }
        .two-column td:first-child,
        .two-column td:nth-child(3) {
            background: #f9fafb;
            color: #374151;
            font-weight: bold;
            width: 18%;
        }
        .badge {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            display: inline-block;
            padding: 2px 8px;
        }
        .footer {
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 10px;
            margin-top: 28px;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attempt Result Report</h1>
        <div class="meta">
            {{ $test['title'] }} | Generated {{ $formatDate($generated_at) }}
        </div>
    </div>

    <h2>Test Summary</h2>
    <table class="two-column">
        <tr>
            <td>Title</td>
            <td>{{ $test['title'] }}</td>
            <td>Owner</td>
            <td>{{ $test['owner'] }}</td>
        </tr>
        <tr>
            <td>Duration</td>
            <td>{{ $test['duration_minutes'] }} minutes</td>
            <td>Pass Mark</td>
            <td>{{ $test['pass_mark'] }}%</td>
        </tr>
    </table>

    <h2>Candidate Summary</h2>
    <table class="two-column">
        <tr>
            <td>Name</td>
            <td>{{ $candidate['name'] ?? 'Not provided' }}</td>
            <td>Email</td>
            <td>{{ $candidate['email'] ?? 'Not provided' }}</td>
        </tr>
        <tr>
            <td>Phone</td>
            <td>{{ $candidate['phone'] ?? 'Not provided' }}</td>
            <td>Stack</td>
            <td>{{ $candidate['stack_name'] ?? 'Not provided' }}</td>
        </tr>
    </table>

    @if (! empty($candidate['fields']))
        <table>
            <thead>
                <tr>
                    <th>Submitted Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($candidate['fields'] as $key => $value)
                    <tr>
                        <td>{{ $formatLabel((string) $key) }}</td>
                        <td>{{ $formatValue($value) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Attempt Summary</h2>
    <table class="two-column">
        <tr>
            <td>Status</td>
            <td><span class="badge">{{ $formatLabel($attempt['status']) }}</span></td>
            <td>Result</td>
            <td><span class="badge">{{ $resultLabel }}</span></td>
        </tr>
        <tr>
            <td>Score</td>
            <td>{{ $attempt['score'] }} / {{ $attempt['max_score'] }}</td>
            <td>Percentage</td>
            <td>{{ $attempt['percentage'] === null ? 'Pending' : number_format($attempt['percentage'], 2) . '%' }}</td>
        </tr>
        <tr>
            <td>Started</td>
            <td>{{ $formatDate($attempt['started_at']) }}</td>
            <td>Submitted</td>
            <td>{{ $formatDate($attempt['submitted_at']) }}</td>
        </tr>
    </table>

    <h2>Question Summary</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Question</th>
                <th>Marks</th>
                <th>Score</th>
                <th>Status</th>
                <th>Coding Test Cases</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($questions as $index => $question)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $formatLabel($question['type']) }}</td>
                    <td>{{ $question['body'] }}</td>
                    <td>{{ $question['marks'] }}</td>
                    <td>{{ $question['score'] }}</td>
                    <td>
                        @if ($question['type'] === 'coding')
                            {{ $question['language'] ? 'Language: '.$question['language'] : 'No language' }}
                            @if ($question['coding_status'])
                                <br>Status: {{ $formatLabel($question['coding_status']) }}
                            @endif
                        @elseif ($question['is_correct'] === null)
                            Pending
                        @else
                            {{ $question['is_correct'] ? 'Correct' : 'Incorrect' }}
                        @endif
                    </td>
                    <td>
                        @if ($question['type'] === 'coding')
                            Visible:
                            {{ $question['visible_summary']['passed'] ?? 0 }}/{{ $question['visible_summary']['total'] ?? 0 }}
                            <br>
                            Hidden:
                            {{ $question['hidden_summary']['passed'] ?? 0 }}/{{ $question['hidden_summary']['total'] ?? 0 }}
                        @else
                            Not applicable
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No answers were saved for this attempt.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Proctoring Summary</h2>
    <table class="two-column">
        <tr>
            <td>Total Events</td>
            <td>{{ $proctoring_summary['total'] }}</td>
            <td>High / Medium / Low</td>
            <td>{{ $proctoring_summary['high'] }} / {{ $proctoring_summary['medium'] }} / {{ $proctoring_summary['low'] }}</td>
        </tr>
        <tr>
            <td>Tab Switches</td>
            <td>{{ $proctoring_summary['tab_switches'] }}</td>
            <td>Fullscreen Exits</td>
            <td>{{ $proctoring_summary['fullscreen_exits'] }}</td>
        </tr>
        <tr>
            <td>Clipboard Attempts</td>
            <td>{{ $proctoring_summary['clipboard_attempts'] }}</td>
            <td>Right-click Attempts</td>
            <td>{{ $proctoring_summary['right_click_attempts'] }}</td>
        </tr>
        <tr>
            <td>Shortcut Attempts</td>
            <td>{{ $proctoring_summary['shortcut_attempts'] }}</td>
            <td>Recording Denials</td>
            <td>{{ $proctoring_summary['recording_permission_denials'] }}</td>
        </tr>
        <tr>
            <td>Screen Share Ended</td>
            <td>{{ $proctoring_summary['screen_share_ended'] }}</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <h2>Proctoring Review Decision</h2>
    <table class="two-column">
        <tr>
            <td>Status</td>
            <td><span class="badge">{{ $formatLabel($proctoring_review['status']) }}</span></td>
            <td>Risk Level</td>
            <td>{{ $formatLabel($proctoring_review['risk_level']) }}</td>
        </tr>
        <tr>
            <td>Reviewed By</td>
            <td>{{ $proctoring_review['reviewed_by'] ?? 'Not reviewed' }}</td>
            <td>Reviewed At</td>
            <td>{{ $formatDate($proctoring_review['reviewed_at']) }}</td>
        </tr>
        <tr>
            <td>Reasons</td>
            <td colspan="3">
                {{ empty($proctoring_review['reason_codes']) ? 'None recorded' : collect($proctoring_review['reason_codes'])->map(fn ($reason) => $formatLabel($reason))->implode(', ') }}
            </td>
        </tr>
        <tr>
            <td>Notes</td>
            <td colspan="3">{{ $proctoring_review['notes'] ?? 'No notes recorded' }}</td>
        </tr>
    </table>

    <div class="footer">
        Generated by Online Exam Platform at {{ $formatDate($generated_at) }}.
    </div>
</body>
</html>
