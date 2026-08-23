<?php

namespace App\Support;

/**
 * The reference codes the console prints, read back as record ids.
 *
 * Every table in the staff console labels a row with a padded, prefixed code —
 * BK-0004 for a booking, PMT-0012 for a payment, GS-0003 for a guest — and one
 * of them even ships a copy-to-clipboard button next to it. None of that is
 * stored: the code is `str_pad($model->id, 4, '0', STR_PAD_LEFT)` rendered at
 * display time, and the searches behind those tables all matched the raw `id`
 * column.
 *
 * So the code the console tells staff to identify a booking by was the one
 * string that could not be used to find it. Pasting BK-0004 returned nothing,
 * with no hint that the prefix was the problem — it read as "that booking is
 * gone", which for a desk chasing a guest at the counter is the worst possible
 * wrong answer.
 *
 * This turns any of those spellings back into the id: "BK-0004", "bk 4",
 * "#0004", "0004" and "4" all mean booking 4. Callers add it as an extra OR
 * against `id` rather than replacing what they already do, so the partial
 * digit matching staff are used to ("4" finding 4, 14 and 40) still works.
 */
class RefCode
{
    /**
     * The prefixes actually rendered in the console, longest first so PMT is
     * not matched as P… by a shorter alternative.
     *
     * BK  bookings          GS   guest (users)
     * PMT payments          DR   discount requests
     * R   receipt number on the paid-booking email
     */
    private const PREFIXES = 'PMT|BK|GS|DR|R';

    /**
     * The id a reference code refers to, or null if the term is not one.
     *
     * Null means "this is not a code" — a guest name, a room number, an empty
     * box — and callers should simply not add the id clause. It is never an
     * error: search terms are typed by people and most of them are not codes.
     */
    public static function toId(?string $term): ?int
    {
        $term = strtoupper(trim((string) $term));

        // A leading # is how people write a record number when the prefix has
        // slipped their mind.
        $term = ltrim($term, '#');

        // The separator is optional and may be a dash, an underscore or a
        // space — staff read the code off a screen and retype it by hand.
        $term = preg_replace('/^(' . self::PREFIXES . ')[\s\-_]*/', '', $term, 1);

        if ($term === '' || ! ctype_digit($term)) {
            return null;
        }

        // Longer than any id will ever be, and past the point where casting to
        // int stops being lossless.
        if (strlen($term) > 18) {
            return null;
        }

        $id = (int) $term;

        return $id > 0 ? $id : null;
    }
}
