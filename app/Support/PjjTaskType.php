<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Metode tugas PJJ: label, validasi struktur soal buatan guru,
 * sanitasi soal untuk siswa (tanpa kunci jawaban), dan penilaian otomatis.
 */
class PjjTaskType
{
    public const ESSAY = 'essay';
    public const UPLOAD = 'upload';
    public const MULTIPLE_CHOICE = 'multiple_choice';
    public const MATCHING = 'matching';
    public const SEQUENCE = 'sequence';
    public const FILL_BLANK = 'fill_blank';
    public const HOTSPOT = 'hotspot';
    public const CATEGORY_SORT = 'category_sort';

    public const LABELS = [
        self::ESSAY => 'Essay',
        self::UPLOAD => 'Upload Tugas',
        self::MULTIPLE_CHOICE => 'Pilihan Ganda',
        self::MATCHING => 'Mencocokkan Pasangan',
        self::SEQUENCE => 'Mengurutkan Urutan',
        self::FILL_BLANK => 'Mengisi Bagian Rumpang',
        self::HOTSPOT => 'Menandai Gambar',
        self::CATEGORY_SORT => 'Klasifikasi Kategori',
    ];

    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(?string $type): string
    {
        return self::LABELS[$type] ?? self::LABELS[self::ESSAY];
    }

    /** Tipe yang membutuhkan soal terstruktur dan dinilai otomatis. */
    public static function isInteractive(?string $type): bool
    {
        return in_array($type, [
            self::MULTIPLE_CHOICE,
            self::MATCHING,
            self::SEQUENCE,
            self::FILL_BLANK,
            self::HOTSPOT,
            self::CATEGORY_SORT,
        ], true);
    }

    /**
     * Validasi struktur soal dari builder guru. Mengembalikan struktur bersih.
     *
     * @throws ValidationException
     */
    public static function validateQuestions(string $type, mixed $questions): ?array
    {
        if (!self::isInteractive($type)) {
            return null;
        }

        if (is_string($questions)) {
            $questions = json_decode($questions, true);
        }

        if (!is_array($questions)) {
            self::fail('Struktur soal tidak valid. Susun ulang soal pada builder.');
        }

        return match ($type) {
            self::MULTIPLE_CHOICE => self::validateMultipleChoice($questions),
            self::MATCHING => self::validateMatching($questions),
            self::SEQUENCE => self::validateSequence($questions),
            self::FILL_BLANK => self::validateFillBlank($questions),
            self::HOTSPOT => self::validateHotspot($questions),
            self::CATEGORY_SORT => self::validateCategorySort($questions),
        };
    }

    /** Soal untuk dikerjakan siswa — kunci jawaban dibuang, pilihan diacak. */
    public static function sanitizeForStudent(string $type, ?array $questions): array
    {
        $questions ??= [];

        return match ($type) {
            self::MULTIPLE_CHOICE => [
                'items' => array_map(fn(array $q) => [
                    'question' => $q['question'],
                    'options' => $q['options'],
                ], $questions['items'] ?? []),
            ],
            self::MATCHING => [
                'lefts' => array_map(fn(array $pair) => $pair['left'], $questions['pairs'] ?? []),
                'rights' => self::shuffled(array_map(fn(array $pair) => $pair['right'], $questions['pairs'] ?? [])),
            ],
            self::SEQUENCE => [
                'instruction' => $questions['instruction'] ?? '',
                'items' => self::shuffled($questions['items'] ?? []),
            ],
            self::FILL_BLANK => (function () use ($questions) {
                [$parts, $answers] = self::parseBlankText((string) ($questions['text'] ?? ''));
                $bank = array_merge($answers, $questions['distractors'] ?? []);

                return [
                    'parts' => $parts,
                    'blank_count' => count($answers),
                    'word_bank' => self::shuffled(array_values(array_unique($bank))),
                ];
            })(),
            self::HOTSPOT => [
                'labels' => self::shuffled(array_map(fn(array $label) => $label['name'], $questions['labels'] ?? [])),
            ],
            self::CATEGORY_SORT => [
                'categories' => array_map(fn(array $cat) => $cat['name'], $questions['categories'] ?? []),
                'items' => self::shuffled(array_merge(...array_map(
                    fn(array $cat) => $cat['items'],
                    $questions['categories'] ?? [['items' => []]]
                ))),
            ],
            default => [],
        };
    }

    /**
     * Nilai jawaban siswa (0-100) terhadap kunci di $questions.
     *
     * @throws ValidationException
     */
    public static function grade(string $type, ?array $questions, mixed $answers): float
    {
        if (is_string($answers)) {
            $answers = json_decode($answers, true);
        }

        if (!is_array($answers)) {
            self::fail('Jawaban tidak terbaca. Kerjakan ulang tugas lalu kirim kembali.');
        }

        $questions ??= [];

        [$correct, $total] = match ($type) {
            self::MULTIPLE_CHOICE => self::gradeMultipleChoice($questions, $answers),
            self::MATCHING => self::gradeMatching($questions, $answers),
            self::SEQUENCE => self::gradeSequence($questions, $answers),
            self::FILL_BLANK => self::gradeFillBlank($questions, $answers),
            self::HOTSPOT => self::gradeHotspot($questions, $answers),
            self::CATEGORY_SORT => self::gradeCategorySort($questions, $answers),
            default => [0, 0],
        };

        return $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;
    }

    // ---------------------------------------------------------------
    // Validasi per tipe
    // ---------------------------------------------------------------

    private static function validateMultipleChoice(array $questions): array
    {
        $items = $questions['items'] ?? null;
        if (!is_array($items) || $items === []) {
            self::fail('Tambahkan minimal 1 soal pilihan ganda.');
        }

        $clean = [];
        foreach (array_values($items) as $i => $item) {
            $question = trim((string) ($item['question'] ?? ''));
            $options = array_values(array_filter(
                array_map(fn($opt) => trim((string) $opt), (array) ($item['options'] ?? [])),
                fn($opt) => $opt !== ''
            ));
            $correct = $item['correct'] ?? null;

            if ($question === '' || count($options) < 2) {
                self::fail('Soal #' . ($i + 1) . ': isi pertanyaan dan minimal 2 pilihan jawaban.');
            }
            if (!is_numeric($correct) || !isset($options[(int) $correct])) {
                self::fail('Soal #' . ($i + 1) . ': tentukan kunci jawaban yang benar.');
            }

            $clean[] = ['question' => $question, 'options' => $options, 'correct' => (int) $correct];
        }

        return ['items' => $clean];
    }

    private static function validateMatching(array $questions): array
    {
        $pairs = [];
        foreach ((array) ($questions['pairs'] ?? []) as $pair) {
            $left = trim((string) ($pair['left'] ?? ''));
            $right = trim((string) ($pair['right'] ?? ''));
            if ($left !== '' && $right !== '') {
                $pairs[] = ['left' => $left, 'right' => $right];
            }
        }

        if (count($pairs) < 2) {
            self::fail('Tambahkan minimal 2 pasangan untuk dicocokkan.');
        }

        return ['pairs' => $pairs];
    }

    private static function validateSequence(array $questions): array
    {
        $items = array_values(array_filter(
            array_map(fn($item) => trim((string) $item), (array) ($questions['items'] ?? [])),
            fn($item) => $item !== ''
        ));

        if (count($items) < 2) {
            self::fail('Tambahkan minimal 2 langkah untuk diurutkan (tuliskan dalam urutan yang benar).');
        }

        return [
            'instruction' => trim((string) ($questions['instruction'] ?? '')),
            'items' => $items,
        ];
    }

    private static function validateFillBlank(array $questions): array
    {
        $text = trim((string) ($questions['text'] ?? ''));
        [, $answers] = self::parseBlankText($text);

        if ($text === '' || $answers === []) {
            self::fail('Tulis teks rumpang dengan jawaban di dalam kurung siku, contoh: Ibu kota Indonesia adalah [Jakarta].');
        }

        $distractors = array_values(array_filter(
            array_map(fn($word) => trim((string) $word), (array) ($questions['distractors'] ?? [])),
            fn($word) => $word !== ''
        ));

        return ['text' => $text, 'distractors' => $distractors];
    }

    private static function validateHotspot(array $questions): array
    {
        $labels = [];
        foreach ((array) ($questions['labels'] ?? []) as $label) {
            $name = trim((string) ($label['name'] ?? ''));
            $x = $label['x'] ?? null;
            $y = $label['y'] ?? null;
            if ($name === '' || !is_numeric($x) || !is_numeric($y)) {
                continue;
            }
            $labels[] = [
                'name' => $name,
                'x' => round(max(0, min(100, (float) $x)), 2),
                'y' => round(max(0, min(100, (float) $y)), 2),
            ];
        }

        if ($labels === []) {
            self::fail('Unggah gambar pada lampiran lalu tandai minimal 1 titik label pada gambar.');
        }

        return [
            'labels' => $labels,
            'tolerance' => max(5, min(25, (float) ($questions['tolerance'] ?? 12))),
        ];
    }

    private static function validateCategorySort(array $questions): array
    {
        $categories = [];
        foreach ((array) ($questions['categories'] ?? []) as $category) {
            $name = trim((string) ($category['name'] ?? ''));
            $items = array_values(array_filter(
                array_map(fn($item) => trim((string) $item), (array) ($category['items'] ?? [])),
                fn($item) => $item !== ''
            ));
            if ($name !== '' && $items !== []) {
                $categories[] = ['name' => $name, 'items' => $items];
            }
        }

        if (count($categories) < 2) {
            self::fail('Buat minimal 2 kategori yang masing-masing berisi minimal 1 item.');
        }

        return ['categories' => $categories];
    }

    // ---------------------------------------------------------------
    // Penilaian per tipe: [benar, total]
    // ---------------------------------------------------------------

    private static function gradeMultipleChoice(array $questions, array $answers): array
    {
        $items = $questions['items'] ?? [];
        $correct = 0;
        foreach (array_values($items) as $i => $item) {
            if (isset($answers[$i]) && (int) $answers[$i] === (int) $item['correct']) {
                $correct++;
            }
        }

        return [$correct, count($items)];
    }

    private static function gradeMatching(array $questions, array $answers): array
    {
        $pairs = $questions['pairs'] ?? [];
        $correct = 0;
        foreach (array_values($pairs) as $i => $pair) {
            if (self::sameText($answers[$i] ?? null, $pair['right'])) {
                $correct++;
            }
        }

        return [$correct, count($pairs)];
    }

    private static function gradeSequence(array $questions, array $answers): array
    {
        $items = $questions['items'] ?? [];
        $correct = 0;
        foreach (array_values($items) as $i => $item) {
            if (self::sameText($answers[$i] ?? null, $item)) {
                $correct++;
            }
        }

        return [$correct, count($items)];
    }

    private static function gradeFillBlank(array $questions, array $answers): array
    {
        [, $keys] = self::parseBlankText((string) ($questions['text'] ?? ''));
        $correct = 0;
        foreach (array_values($keys) as $i => $key) {
            if (self::sameText($answers[$i] ?? null, $key)) {
                $correct++;
            }
        }

        return [$correct, count($keys)];
    }

    private static function gradeHotspot(array $questions, array $answers): array
    {
        $labels = $questions['labels'] ?? [];
        $tolerance = (float) ($questions['tolerance'] ?? 12);
        $correct = 0;

        foreach ($labels as $label) {
            foreach ($answers as $answer) {
                if (!is_array($answer) || !self::sameText($answer['label'] ?? null, $label['name'])) {
                    continue;
                }
                $dx = (float) ($answer['x'] ?? -999) - (float) $label['x'];
                $dy = (float) ($answer['y'] ?? -999) - (float) $label['y'];
                if (sqrt($dx * $dx + $dy * $dy) <= $tolerance) {
                    $correct++;
                }
                break;
            }
        }

        return [$correct, count($labels)];
    }

    private static function gradeCategorySort(array $questions, array $answers): array
    {
        $correct = 0;
        $total = 0;
        foreach ($questions['categories'] ?? [] as $category) {
            foreach ($category['items'] as $item) {
                $total++;
                $chosen = null;
                foreach ($answers as $answerItem => $answerCategory) {
                    if (self::sameText((string) $answerItem, $item)) {
                        $chosen = $answerCategory;
                        break;
                    }
                }
                if (self::sameText($chosen, $category['name'])) {
                    $correct++;
                }
            }
        }

        return [$correct, $total];
    }

    // ---------------------------------------------------------------
    // Util
    // ---------------------------------------------------------------

    /** Pecah teks rumpang "… [jawaban] …" menjadi potongan teks dan daftar kunci. */
    public static function parseBlankText(string $text): array
    {
        $answers = [];
        $parts = preg_split('/\[([^\[\]]+)\]/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $textParts = [];

        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                $answers[] = trim($part);
            } else {
                $textParts[] = $part;
            }
        }

        return [$textParts, $answers];
    }

    private static function sameText(mixed $a, mixed $b): bool
    {
        return is_scalar($a) && is_scalar($b)
            && mb_strtolower(trim((string) $a)) === mb_strtolower(trim((string) $b));
    }

    private static function shuffled(array $items): array
    {
        $items = array_values($items);
        shuffle($items);

        return $items;
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['questions' => $message]);
    }
}
