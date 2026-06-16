import Editor from '@monaco-editor/react';

type Props = {
    language: string;
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
};

const monacoLanguageByKey: Record<string, string> = {
    php: 'php',
    javascript: 'javascript',
    python: 'python',
    java: 'java',
    cpp: 'cpp',
};

export default function MonacoCodeEditor({
    language,
    value,
    onChange,
    disabled = false,
}: Props) {
    return (
        <Editor
            height="360px"
            theme="vs-dark"
            language={monacoLanguageByKey[language] ?? language}
            value={value}
            loading={
                <div className="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                    Loading editor...
                </div>
            }
            options={{
                minimap: { enabled: false },
                fontSize: 14,
                scrollBeyondLastLine: false,
                automaticLayout: true,
                readOnly: disabled,
            }}
            onChange={(nextValue) => onChange(nextValue ?? '')}
        />
    );
}
