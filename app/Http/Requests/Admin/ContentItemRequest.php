<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validasi doa/dzikir — komponen wajib: Arab, Latin, arti, sumber (Standar Wajib Bacaan). @2026-Alifiandra
class ContentItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('content.edit') ?? false;
    }

    public function rules(): array
    {
        $type = $this->route('type');
        $table = match ($type) { 'doa' => 'dua', 'panduan' => 'guide', default => 'dhikr' };
        $id = $this->route('id');

        if ($type === 'panduan') {
            return [
                'category_id' => ['required', 'integer', Rule::exists('guide_categories', 'id')],
                'title' => ['required', 'string', 'max:160'],
                'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('guide_items', 'slug')->ignore($id)],
                'body' => ['required', 'string', 'max:20000'],
                'arabic_text' => ['nullable', 'string', 'max:5000'],
                'transliteration' => ['nullable', 'required_with:arabic_text', 'string', 'max:5000'],
                'translation' => ['nullable', 'required_with:arabic_text', 'string', 'max:5000'],
                'example_ref' => ['nullable', 'string', 'regex:/^\d{1,3}:\d{1,3}$/'],
                'source_reference' => ['required', 'string', 'max:255'],
                'source_kind' => ['required', Rule::in(['quran', 'hadith', 'general'])],
                'context_note' => ['nullable', 'string', 'max:1000'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            ];
        }

        return [
            'category_id' => ['required', 'integer', Rule::exists("{$table}_categories", 'id')],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique("{$table}_items", 'slug')->ignore($id)],
            'arabic_text' => ['required', 'string', 'max:5000'],
            'transliteration' => ['required', 'string', 'max:5000'],
            'translation' => ['required', 'string', 'max:5000'],
            'source_reference' => ['required', 'string', 'max:255'],
            'source_kind' => ['required', Rule::in(['quran', 'hadith', 'general'])],
            'count_target' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'context_note' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'kategori', 'title' => 'judul', 'arabic_text' => 'teks Arab', 'transliteration' => 'transliterasi Latin',
            'translation' => 'arti Bahasa Indonesia', 'source_reference' => 'rujukan sumber', 'source_kind' => 'jenis sumber',
            'count_target' => 'jumlah anjuran', 'context_note' => 'keterangan konteks', 'body' => 'penjelasan', 'example_ref' => 'contoh ayat',
        ];
    }
}
