@props(['model', 'value' => '', 'label' => __('Description'), 'rows' => 12])

@once
    <script>
        (() => {
            const registerTaskMarkdownEditor = () => {
                if (window.taskMarkdownEditorRegistered || ! window.Alpine) {
                    return;
                }

                window.taskMarkdownEditorRegistered = true;
                window.Alpine.data('taskMarkdownEditor', (initialHtml = '') => ({
                    initialHtml,
                    initialize() {
                        this.$refs.editor.innerHTML = this.initialHtml || '';
                        this.sync();
                    },
                    command(name, value = null) {
                        document.execCommand(name, false, value);
                        this.$refs.editor.focus();
                        this.sync();
                    },
                    formatBlock(tag) {
                        this.command('formatBlock', tag);
                    },
                    insertLink() {
                        const url = window.prompt('https://');

                        if (url) {
                            this.command('createLink', url);
                        }
                    },
                    sync() {
                        this.$refs.markdown.value = this.toMarkdown(this.$refs.editor);
                        this.$refs.markdown.dispatchEvent(new Event('input', { bubbles: true }));
                    },
                    inline(node) {
                        if (node.nodeType === Node.TEXT_NODE) {
                            return node.textContent.replace(/\s+/g, ' ');
                        }

                        if (node.nodeType !== Node.ELEMENT_NODE) {
                            return '';
                        }

                        const content = [...node.childNodes].map((child) => this.inline(child)).join('');
                        const tag = node.tagName.toLowerCase();

                        if (tag === 'br') return '\n';
                        if (tag === 'strong' || tag === 'b') return `**${content}**`;
                        if (tag === 'em' || tag === 'i') return `*${content}*`;
                        if (tag === 'code') return `\`${content}\``;
                        if (tag === 'a') return `[${content}](${node.getAttribute('href') || ''})`;

                        return content;
                    },
                    block(node, depth = 0) {
                        if (node.nodeType === Node.TEXT_NODE) {
                            return node.textContent.trim();
                        }

                        if (node.nodeType !== Node.ELEMENT_NODE) {
                            return '';
                        }

                        const tag = node.tagName.toLowerCase();
                        const children = [...node.children];

                        if (/^h[1-6]$/.test(tag)) return `${'#'.repeat(Number(tag[1]))} ${this.inline(node).trim()}`;
                        if (tag === 'p' || tag === 'div') return this.inline(node).trim();
                        if (tag === 'blockquote') return this.inline(node).trim().split('\n').map((line) => `> ${line}`).join('\n');
                        if (tag === 'pre') return `\`\`\`\n${node.textContent.trim()}\n\`\`\``;

                        if (tag === 'ul' || tag === 'ol') {
                            return children.map((item, index) => {
                                const marker = tag === 'ol' ? `${index + 1}.` : '-';
                                const nested = [...item.children].filter((child) => ['UL', 'OL'].includes(child.tagName));
                                const clone = item.cloneNode(true);

                                [...clone.children]
                                    .filter((child) => ['UL', 'OL'].includes(child.tagName))
                                    .forEach((child) => child.remove());

                                const line = `${'  '.repeat(depth)}${marker} ${this.inline(clone).trim()}`;

                                return [line, ...nested.map((child) => this.block(child, depth + 1))]
                                    .filter(Boolean)
                                    .join('\n');
                            }).join('\n');
                        }

                        return this.inline(node).trim();
                    },
                    toMarkdown(root) {
                        return [...root.childNodes]
                            .map((node) => this.block(node))
                            .filter(Boolean)
                            .join('\n\n')
                            .trim();
                    },
                }));
            };

            document.addEventListener('alpine:init', registerTaskMarkdownEditor, { once: true });
            registerTaskMarkdownEditor();
        })();
    </script>
@endonce

<div class="overflow-hidden rounded-2xl border border-[#dfe3dc] bg-white shadow-sm focus-within:border-[#668f55] focus-within:ring-2 focus-within:ring-[#668f55]/20" x-data="taskMarkdownEditor(@js(str($value)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false])))">
    <div class="flex flex-wrap items-center gap-1 border-b border-[#e6e9e3] bg-[#f8faf6] p-2" role="toolbar" aria-label="{{ __('Text formatting') }}">
        <button type="button" class="markdown-tool" @mousedown.prevent="formatBlock('h2')" aria-label="{{ __('Heading') }}" title="{{ __('Heading') }}">{{ 'H' }}</button>
        <button type="button" class="markdown-tool font-bold" @mousedown.prevent="command('bold')" aria-label="{{ __('Bold') }}" title="{{ __('Bold') }}">{{ 'B' }}</button>
        <button type="button" class="markdown-tool italic" @mousedown.prevent="command('italic')" aria-label="{{ __('Italic') }}" title="{{ __('Italic') }}">{{ 'I' }}</button>
        <button type="button" class="markdown-tool" @mousedown.prevent="command('insertUnorderedList')" aria-label="{{ __('Bulleted list') }}" title="{{ __('Bulleted list') }}">{{ '•' }}</button>
        <button type="button" class="markdown-tool" @mousedown.prevent="command('insertOrderedList')" aria-label="{{ __('Numbered list') }}" title="{{ __('Numbered list') }}">{{ '1.' }}</button>
        <button type="button" class="markdown-tool" @mousedown.prevent="formatBlock('blockquote')" aria-label="{{ __('Quote') }}" title="{{ __('Quote') }}">{{ '“' }}</button>
        <button type="button" class="markdown-tool font-mono" @mousedown.prevent="formatBlock('pre')" aria-label="{{ __('Code block') }}" title="{{ __('Code block') }}">{{ '</>' }}</button>
        <button type="button" class="markdown-tool" @mousedown.prevent="insertLink()" aria-label="{{ __('Insert link') }}" title="{{ __('Insert link') }}">{{ '↗' }}</button>
        <button type="button" class="markdown-tool" @mousedown.prevent="command('removeFormat')" aria-label="{{ __('Clear formatting') }}" title="{{ __('Clear formatting') }}">{{ '×' }}</button>
    </div>
    <label class="sr-only" for="editor-{{ str($model)->slug() }}">{{ $label }}</label>
    <div id="editor-{{ str($model)->slug() }}" x-ref="editor" x-init="initialize()" wire:ignore contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="{{ __('Write the description…') }}" @input="sync()" @blur="sync()" class="task-rich-editor prose max-w-none overflow-y-auto p-4 text-sm text-[#292e29] outline-none" style="min-height: {{ max(8, (int) $rows) * 1.5 }}rem"></div>
    <textarea x-ref="markdown" wire:model="{{ $model }}" class="hidden" aria-hidden="true" tabindex="-1"></textarea>
    <div class="border-t border-[#edf0ea] px-4 py-2 text-xs text-[#737a72]">{{ __('Formatting is saved as Markdown automatically.') }}</div>
</div>
