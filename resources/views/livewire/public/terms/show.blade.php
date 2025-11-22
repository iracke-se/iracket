<div>
    <style>
        .terms-content h1 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #18181b;
        }

        .dark .terms-content h1 {
            color: #fafafa;
        }

        @media (min-width: 768px) {
            .terms-content h1 {
                font-size: 2.25rem;
            }
        }

        .terms-content h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #3f3f46;
        }

        .dark .terms-content h2 {
            color: #d4d4d8;
        }

        @media (min-width: 768px) {
            .terms-content h2 {
                font-size: 1.5rem;
            }
        }

        .terms-content h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #52525b;
        }

        .dark .terms-content h3 {
            color: #a1a1aa;
        }

        .terms-content p {
            line-height: 1.625;
            margin-bottom: 1rem;
        }

        .terms-content ul {
            list-style-type: disc;
            list-style-position: outside;
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .terms-content ol {
            list-style-type: decimal;
            list-style-position: outside;
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .terms-content li {
            margin-bottom: 0.5rem;
        }

        .terms-content a {
            color: var(--color-accent);
        }

        .terms-content a:hover {
            text-decoration: underline;
        }

        .terms-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        .terms-content th,
        .terms-content td {
            padding: 0.75rem;
            text-align: left;
        }

        .terms-content thead tr {
            border-bottom: 2px solid #e4e4e7;
        }

        .dark .terms-content thead tr {
            border-bottom: 2px solid #404040;
        }

        .terms-content tbody tr {
            border-bottom: 1px solid #e4e4e7;
        }

        .dark .terms-content tbody tr {
            border-bottom: 1px solid #404040;
        }

        .terms-content th {
            color: #18181b;
            font-weight: 600;
        }

        .dark .terms-content th {
            color: #fafafa;
        }
    </style>

    <div class="flex flex-col gap-6 w-full max-w-[1000px] mx-auto px-4">
        <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-8 md:p-12">
            <div class="terms-content text-zinc-600 dark:text-zinc-400">
                {!! $term->content !!}
            </div>

            <div class="mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Last updated') }}: {{ $term->updated_at->format('F j, Y') }}
            </div>
        </div>
    </div>
</div>
