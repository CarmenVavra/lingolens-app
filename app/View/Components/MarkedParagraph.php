<?php

namespace App\View\Components;

use App\Models\Correction;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/**
 * One paragraph of the student text with interactive error marks.
 *
 * Rendered in PHP because whitespace between inline segments is significant
 * (e.g. a mark directly followed by a full stop). Every value is escaped.
 */
class MarkedParagraph extends Component
{
    /**
     * @param  list<array{text: string, correction: Correction|null}>  $segments
     */
    public function __construct(public array $segments) {}

    public function render(): HtmlString
    {
        $html = '';

        foreach ($this->segments as $segment) {
            $html .= $segment['correction']
                ? $this->mark($segment['correction'], $segment['text'])
                : e($segment['text']);
        }

        return new HtmlString('<p>'.$html.'</p>');
    }

    private function mark(Correction $correction, string $text): string
    {
        $id = $correction->id;
        $hidden = 'isHidden('.json_encode($correction->error_type->value).')';

        $attributes = [
            'class' => 'mark is-'.$correction->error_type->slug(),
            'data-correction' => (string) $id,
            'role' => 'button',
            'tabindex' => '0',
            ':class' => "markState({$id})",
            ':role' => "{$hidden} ? null : 'button'",
            ':tabindex' => "{$hidden} ? null : 0",
            ':aria-label' => "{$hidden} ? null : markLabel({$id})",
            ':aria-pressed' => "{$hidden} ? null : (selectedId === {$id}).toString()",
            '@click' => "select({$id})",
            '@keydown.enter.prevent' => "select({$id})",
            '@keydown.space.prevent' => "select({$id})",
            'x-text' => "markText({$id})",
        ];

        $rendered = collect($attributes)
            ->map(fn (string $value, string $name) => $name.'="'.e($value).'"')
            ->implode(' ');

        return '<span '.$rendered.'>'.e($text).'</span>';
    }
}
