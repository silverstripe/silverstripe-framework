<?php
/*
 * Copyright (C) 2000, 2001 Geoffrey T. Dairiki <dairiki@dairiki.org>
 *
 * This file is part of PhpWiki.
 *
 * PhpWiki is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * PhpWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with PhpWiki; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

// difflib.php
//
// A PHP diff engine for phpwiki.

abstract class DiffOp
{
    public $type;
    public $orig;
    public $final;

    abstract public function reverse();

    public function norig()
    {
        return $this->orig ? sizeof($this->orig) : 0;
    }

    public function nfinal()
    {
        return $this->final ? sizeof($this->final) : 0;
    }
}

class DiffOp_Copy extends DiffOp
{
    public $type = 'copy';

    function __construct($orig, $final = false)
    {
        if (!is_array($final))
            $final = $orig;
        $this->orig = $orig;
        $this->final = $final;
    }

    public function reverse()
    {
        return new DiffOp_Copy($this->final, $this->orig);
    }
}

class DiffOp_Delete extends DiffOp
{
    public $type = 'delete';

    function __construct($lines)
    {
        $this->orig = $lines;
        $this->final = false;
    }

    public function reverse()
    {
        return new DiffOp_Add($this->orig);
    }
}

class DiffOp_Add extends DiffOp
{
    public $type = 'add';

    function __construct($lines)
    {
        $this->final = $lines;
        $this->orig = false;
    }

    public function reverse()
    {
        return new DiffOp_Delete($this->final);
    }
}

class DiffOp_Change extends DiffOp
{
    public $type = 'change';

    function __construct($orig, $final)
    {
        $this->orig = $orig;
        $this->final = $final;
    }

    public function reverse()
    {
        return new DiffOp_Change($this->final, $this->orig);
    }
}

/**
 * Class used internally by Diff to actually compute the diffs.
 *
 * The algorithm used here is mostly lifted from the perl module
 * Algorithm::Diff (version 1.06) by Ned Konz, which is available at:
 *   http://www.perl.com/CPAN/authors/id/N/NE/NEDKONZ/Algorithm-Diff-1.06.zip
 *
 * More ideas are taken from:
 *   http://www.ics.uci.edu/~eppstein/161/960229.html
 *
 * Some ideas are (and a bit of code) are from from analyze.c, from GNU
 * diffutils-2.7, which can be found at:
 *   ftp://gnudist.gnu.org/pub/gnu/diffutils/diffutils-2.7.tar.gz
 *
 * Finally, some ideas (subdivision by NCHUNKS > 2, and some optimizations)
 * are my own.
 *
 * @author Geoffrey T. Dairiki
 * @access private
 */
class DiffEngine
{
    public $xchanged;
    public $ychanged;
    public $xv;
    public $yv;
    public $xind;
    public $yind;
    public $lcs;
    public $seq;
    public $in_seq;

    public function diff($from_lines, $to_lines)
    {
        $n_from = sizeof($from_lines);
        $n_to = sizeof($to_lines);

        $this->xchanged = $this->ychanged = array();
        $this->xv = $this->yv = array();
        $this->xind = $this->yind = array();
        unset($this->seq);
        unset($this->in_seq);
        unset($this->lcs);

        // Skip leading common lines.
        for ($skip = 0; $skip < $n_from && $skip < $n_to; $skip++) {
            if ($from_lines[$skip] != $to_lines[$skip])
                break;
            $this->xchanged[$skip] = $this->ychanged[$skip] = false;
        }
        // Skip trailing common lines.
        $xi = $n_from;
        $yi = $n_to;
        for ($endskip = 0; --$xi > $skip && --$yi > $skip; $endskip++) {
            if ($from_lines[$xi] != $to_lines[$yi])
                break;
            $this->xchanged[$xi] = $this->ychanged[$yi] = false;
        }

        // Ignore lines which do not exist in both files.
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++)
            $xhash[$from_lines[$xi]] = 1;
        for ($yi = $skip; $yi < $n_to - $endskip; $yi++) {
            $line = $to_lines[$yi];
            if (($this->ychanged[$yi] = empty($xhash[$line])))
                continue;
            $yhash[$line] = 1;
            $this->yv[] = $line;
            $this->yind[] = $yi;
        }
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
            $line = $from_lines[$xi];
            if (($this->xchanged[$xi] = empty($yhash[$line])))
                continue;
            $this->xv[] = $line;
            $this->xind[] = $xi;
        }

        // Find the LCS.
        $this->compareseq(0, sizeof($this->xv), 0, sizeof($this->yv));

        // Merge edits when possible
        $this->shift_boundaries($from_lines, $this->xchanged, $this->ychanged);
        $this->shift_boundaries($to_lines, $this->ychanged, $this->xchanged);

        // Compute the edit operations.
        $edits = array();
        $xi = $yi = 0;
        while ($xi < $n_from || $yi < $n_to) {
            assert($yi < $n_to || $this->xchanged[$xi]);
            assert($xi < $n_from || $this->ychanged[$yi]);

            // Skip matching "snake".
            $copy = array();
            while ($xi < $n_from && $yi < $n_to
                && !$this->xchanged[$xi] && !$this->ychanged[$yi]) {
                $copy[] = $from_lines[$xi++];
                ++$yi;
            }
            if ($copy)
                $edits[] = new DiffOp_Copy($copy);

            // Find deletes & adds.
            $delete = array();
            while ($xi < $n_from && $this->xchanged[$xi])
                $delete[] = $from_lines[$xi++];

            $add = array();
            while ($yi < $n_to && $this->ychanged[$yi])
                $add[] = $to_lines[$yi++];

            if ($delete && $add)
                $edits[] = new DiffOp_Change($delete, $add);
            elseif ($delete)
                $edits[] = new DiffOp_Delete($delete);
            elseif ($add)
                $edits[] = new DiffOp_Add($add);
        }
        return $edits;
    }

    /* Divide the Largest Common Subsequence (LCS) of the sequences
     * [XOFF, XLIM) and [YOFF, YLIM) into NCHUNKS approximately equally
     * sized segments.
     *
     * Returns (LCS, PTS).  LCS is the length of the LCS. PTS is an
     * array of NCHUNKS+1 (X, Y) indexes giving the diving points between
     * sub sequences.  The first sub-sequence is contained in [X0, X1),
     * [Y0, Y1), the second in [X1, X2), [Y1, Y2) and so on.  Note
     * that (X0, Y0) == (XOFF, YOFF) and
     * (X[NCHUNKS], Y[NCHUNKS]) == (XLIM, YLIM).
     *
     * This function assumes that the first lines of the specified portions
     * of the two files do not match, and likewise that the last lines do not
     * match.  The caller must trim matching lines from the beginning and end
     * of the portions it is going to specify.
     */
    private function diag($xoff, $xlim, $yoff, $ylim, $nchunks)
    {
        $flip = false;

        if ($xlim - $xoff > $ylim - $yoff) {
            // Things seems faster (I'm not sure I understand why)
            // when the shortest sequence in X.
            $flip = true;
            list ($xoff, $xlim, $yoff, $ylim)
                = array($yoff, $ylim, $xoff, $xlim);
        }

        if ($flip)
            for ($i = $ylim - 1; $i >= $yoff; $i--)
                $ymatches[$this->xv[$i]][] = $i;
        else
            for ($i = $ylim - 1; $i >= $yoff; $i--)
                $ymatches[$this->yv[$i]][] = $i;

        $this->lcs = 0;
        $this->seq[0] = $yoff - 1;
        $this->in_seq = array();
        $ymids[0] = array();

        $numer = $xlim - $xoff + $nchunks - 1;
        $x = $xoff;
        for ($chunk = 0; $chunk < $nchunks; $chunk++) {
            if ($chunk > 0)
                for ($i = 0; $i <= $this->lcs; $i++)
                    $ymids[$i][$chunk - 1] = $this->seq[$i];

            $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $chunk) / $nchunks);
            for (; $x < $x1; $x++) {
                $line = $flip ? $this->yv[$x] : $this->xv[$x];
                if (empty($ymatches[$line]))
                    continue;
                $matches = $ymatches[$line];
                reset($matches);
                $pointer = 0;
                foreach ($matches as $y) {
                    $pointer++;
                    if (empty($this->in_seq[$y])) {
                        $k = $this->lcs_pos($y);
                        assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                        break;
                    }
                }
                foreach (array_slice($matches, $pointer) as $y) {
                    if ($y > $this->seq[$k - 1]) {
                        assert($y < $this->seq[$k]);
                        // Optimization: this is a common case:
                        //  next match is just replacing previous match.
                        $this->in_seq[$this->seq[$k]] = false;
                        $this->seq[$k] = $y;
                        $this->in_seq[$y] = 1;
                    } elseif (empty($this->in_seq[$y])) {
                        $k = $this->lcs_pos($y);
                        assert($k > 0);
                        $ymids[$k] = $ymids[$k - 1];
                    }
                }
            }
        }

        $seps[] = $flip ? array($yoff, $xoff) : array($xoff, $yoff);
        $ymid = $ymids[$this->lcs];
        for ($n = 0; $n < $nchunks - 1; $n++) {
            $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $n) / $nchunks);
            $y1 = $ymid[$n] + 1;
            $seps[] = $flip ? array($y1, $x1) : array($x1, $y1);
        }
        $seps[] = $flip ? array($ylim, $xlim) : array($xlim, $ylim);

        return array($this->lcs, $seps);
    }

    private function lcs_pos($ypos)
    {
        $end = $this->lcs;
        if ($end == 0 || $ypos > $this->seq[$end]) {
            $this->seq[++$this->lcs] = $ypos;
            $this->in_seq[$ypos] = 1;
            return $this->lcs;
        }

        $beg = 1;
        while ($beg < $end) {
            $mid = (int)(($beg + $end) / 2);
            if ($ypos > $this->seq[$mid])
                $beg = $mid + 1;
            else
                $end = $mid;
        }

        assert($ypos != $this->seq[$end]);

        $this->in_seq[$this->seq[$end]] = false;
        $this->seq[$end] = $ypos;
        $this->in_seq[$ypos] = 1;
        return $end;
    }

    /* Find LCS of two sequences.
     *
     * The results are recorded in the vectors $this->{x,y}changed[], by
     * storing a 1 in the element for each line that is an insertion
     * or deletion (ie. is not in the LCS).
     *
     * The subsequence of file 0 is [XOFF, XLIM) and likewise for file 1.
     *
     * Note that XLIM, YLIM are exclusive bounds.
     * All line numbers are origin-0 and discarded lines are not counted.
     */
    private function compareseq($xoff, $xlim, $yoff, $ylim)
    {
        // Slide down the bottom initial diagonal.
        while ($xoff < $xlim && $yoff < $ylim
            && $this->xv[$xoff] == $this->yv[$yoff]) {
            ++$xoff;
            ++$yoff;
        }

        // Slide up the top initial diagonal.
        while ($xlim > $xoff && $ylim > $yoff
            && $this->xv[$xlim - 1] == $this->yv[$ylim - 1]) {
            --$xlim;
            --$ylim;
        }

        if ($xoff == $xlim || $yoff == $ylim)
            $lcs = 0;
        else {
            // This is ad hoc but seems to work well.
            //$nchunks = sqrt(min($xlim - $xoff, $ylim - $yoff) / 2.5);
            //$nchunks = max(2,min(8,(int)$nchunks));
            $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
            list ($lcs, $seps)
                = $this->diag($xoff, $xlim, $yoff, $ylim, $nchunks);
        }

        if ($lcs == 0) {
            // X and Y sequences have no common subsequence:
            // mark all changed.
            while ($yoff < $ylim)
                $this->ychanged[$this->yind[$yoff++]] = 1;
            while ($xoff < $xlim)
                $this->xchanged[$this->xind[$xoff++]] = 1;
        } else {
            // Use the partitions to split this problem into subproblems.
            reset($seps);
            $pt1 = $seps[0];
            while ($pt2 = next($seps)) {
                $this->compareseq($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
                $pt1 = $pt2;
            }
        }
    }

    /* Adjust inserts/deletes of identical lines to join changes
     * as much as possible.
     *
     * We do something when a run of changed lines include a
     * line at one end and has an excluded, identical line at the other.
     * We are free to choose which identical line is included.
     * `compareseq' usually chooses the one at the beginning,
     * but usually it is cleaner to consider the following identical line
     * to be the "change".
     *
     * This is extracted verbatim from analyze.c (GNU diffutils-2.7).
     */
    private function shift_boundaries($lines, &$changed, $other_changed)
    {
        $i = 0;
        $j = 0;

        assert(sizeof($lines) == sizeof($changed));
        $len = sizeof($lines);
        $other_len = sizeof($other_changed);

        while (1) {
            /*
             * Scan forwards to find beginning of another run of changes.
             * Also keep track of the corresponding point in the other file.
             *
             * Throughout this code, $i and $j are adjusted together so that
             * the first $i elements of $changed and the first $j elements
             * of $other_changed both contain the same number of zeros
             * (unchanged lines).
             * Furthermore, $j is always kept so that $j == $other_len or
             * $other_changed[$j] == false.
             */
            while ($j < $other_len && $other_changed[$j])
                $j++;

            while ($i < $len && !$changed[$i]) {
                assert($j < $other_len && ! $other_changed[$j]);
                $i++;
                $j++;
                while ($j < $other_len && $other_changed[$j])
                    $j++;
            }

            if ($i == $len)
                break;

            $start = $i;

            // Find the end of this run of changes.
            while (++$i < $len && $changed[$i])
                continue;

            do {
                /*
                 * Record the length of this run of changes, so that
                 * we can later determine whether the run has grown.
                 */
                $runlength = $i - $start;

                /*
                 * Move the changed region back, so long as the
                 * previous unchanged line matches the last changed one.
                 * This merges with previous changed regions.
                 */
                while ($start > 0 && $lines[$start - 1] == $lines[$i - 1]) {
                    $changed[--$start] = 1;
                    $changed[--$i] = false;
                    while ($start > 0 && $changed[$start - 1])
                        $start--;
                    assert($j > 0);
                    while ($other_changed[--$j])
                        continue;
                    assert($j >= 0 && !$other_changed[$j]);
                }

                /*
                 * Set CORRESPONDING to the end of the changed run, at the last
                 * point where it corresponds to a changed run in the other file.
                 * CORRESPONDING == LEN means no such point has been found.
                 */
                $corresponding = $j < $other_len ? $i : $len;

                /*
                 * Move the changed region forward, so long as the
                 * first changed line matches the following unchanged one.
                 * This merges with following changed regions.
                 * Do this second, so that if there are no merges,
                 * the changed region is moved forward as far as possible.
                 */
                while ($i < $len && $lines[$start] == $lines[$i]) {
                    $changed[$start++] = false;
                    $changed[$i++] = 1;
                    while ($i < $len && $changed[$i])
                        $i++;

                    assert($j < $other_len && ! $other_changed[$j]);
                    $j++;
                    if ($j < $other_len && $other_changed[$j]) {
                        $corresponding = $i;
                        while ($j < $other_len && $other_changed[$j])
                            $j++;
                    }
                }
            } while ($runlength != $i - $start);

            /*
             * If possible, move the fully-merged run of changes
             * back to a corresponding run in the other file.
             */
            while ($corresponding < $i) {
                $changed[--$start] = 1;
                $changed[--$i] = 0;
                assert($j > 0);
                while ($other_changed[--$j])
                    continue;
                assert($j >= 0 && !$other_changed[$j]);
            }
        }
    }
}

/**
 * Class representing a 'diff' between two sequences of strings.
 */
class Diff
{
    public $edits;

    /**
     * Computes diff between sequences of strings.
     *
     * @param $from_lines array An array of strings.
     *        (Typically these are lines from a file.)
     * @param $to_lines array An array of strings.
     */
    function __construct($from_lines, $to_lines)
    {
        $eng = new DiffEngine();
        $this->edits = $eng->diff($from_lines, $to_lines);
    }

    /**
     * Check for empty diff.
     *
     * @return bool True iff two sequences were identical.
     */
    public function isEmpty()
    {
        foreach ($this->edits as $edit) {
            if ($edit->type != 'copy')
                return false;
        }
        return true;
    }

    /**
     * Get the original set of lines.
     *
     * This reconstructs the $from_lines parameter passed to the
     * constructor.
     *
     * @return array The original sequence of strings.
     */
    public function orig()
    {
        $lines = array();

        foreach ($this->edits as $edit) {
            if ($edit->orig)
                array_splice($lines, sizeof($lines), 0, $edit->orig);
        }
        return $lines;
    }

    /**
     * Get the final set of lines.
     *
     * This reconstructs the $to_lines parameter passed to the
     * constructor.
     *
     * @return array The sequence of strings.
     */
    public function finalize()
    {
        $lines = array();

        foreach ($this->edits as $edit) {
            if ($edit->final)
                array_splice($lines, sizeof($lines), 0, $edit->final);
        }
        return $lines;
    }
}

/**
 * FIXME: bad name.
 */
class MappedDiff
    extends Diff
{
    /**
     * Computes diff between sequences of strings.
     *
     * This can be used to compute things like
     * case-insensitve diffs, or diffs which ignore
     * changes in white-space.
     *
     * @param $from_lines array An array of strings.
     *  (Typically these are lines from a file.)
     *
     * @param $to_lines array An array of strings.
     *
     * @param $mapped_from_lines array This array should
     *  have the same size number of elements as $from_lines.
     *  The elements in $mapped_from_lines and
     *  $mapped_to_lines are what is actually compared
     *  when computing the diff.
     *
     * @param $mapped_to_lines array This array should
     *  have the same number of elements as $to_lines.
     */
    function __construct($from_lines, $to_lines,
                         $mapped_from_lines, $mapped_to_lines)
    {

        assert(sizeof($from_lines) == sizeof($mapped_from_lines));
        assert(sizeof($to_lines) == sizeof($mapped_to_lines));

        parent::__construct($mapped_from_lines, $mapped_to_lines);

        $xi = $yi = 0;
        // Optimizing loop invariants:
        // http://phplens.com/lens/php-book/optimizing-debugging-php.php
        for ($i = 0, $max = sizeof($this->edits); $i < $max; $i++) {
            $orig = &$this->edits[$i]->orig;
            if (is_array($orig)) {
                $orig = array_slice($from_lines, $xi, sizeof($orig));
                $xi += sizeof($orig);
            }

            $final = &$this->edits[$i]->final;
            if (is_array($final)) {
                $final = array_slice($to_lines, $yi, sizeof($final));
                $yi += sizeof($final);
            }
        }
    }
}

/**
 * A class to format Diffs
 *
 * This class formats the diff in classic diff format.
 * It is intended that this class be customized via inheritance,
 * to obtain fancier outputs.
 */
class DiffFormatter
{
    /**
     * Number of leading context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses
     * may want to set this to other values.
     */
    public $leading_context_lines = 0;

    /**
     * Number of trailing context "lines" to preserve.
     *
     * This should be left at zero for this class, but subclasses
     * may want to set this to other values.
     */
    public $trailing_context_lines = 0;

    /**
     * Format a diff.
     *
     * @param $diff object A Diff object.
     * @return string The formatted output.
     */
    public function format($diff)
    {

        $xi = $yi = 1;
        $block = false;
        $context = array();

        $nlead = $this->leading_context_lines;
        $ntrail = $this->trailing_context_lines;

        $this->start_diff();
        $x0 = 0;
        $y0 = 0;

        foreach ($diff->edits as $edit) {
            if ($edit->type == 'copy') {
                if (is_array($block)) {
                    if (sizeof($edit->orig) <= $nlead + $ntrail) {
                        $block[] = $edit;
                    } else {
                        if ($ntrail) {
                            $context = array_slice($edit->orig, 0, $ntrail);
                            $block[] = new DiffOp_Copy($context);
                        }
                        $this->block($x0, $ntrail + $xi - $x0,
                            $y0, $ntrail + $yi - $y0,
                            $block);
                        $block = false;
                    }
                }
                $context = $edit->orig;
            } else {
                if (!is_array($block)) {
                    $context = array_slice($context, max(0, sizeof($context) - $nlead));
                    $x0 = $xi - sizeof($context);
                    $y0 = $yi - sizeof($context);
                    $block = array();
                    if ($context)
                        $block[] = new DiffOp_Copy($context);
                }
                $block[] = $edit;
            }

            if ($edit->orig)
                $xi += sizeof($edit->orig);
            if ($edit->final)
                $yi += sizeof($edit->final);
        }

        if (is_array($block))
            $this->block($x0, $xi - $x0,
                $y0, $yi - $y0,
                $block);

        return $this->end_diff();
    }

    private function block($xbeg, $xlen, $ybeg, $ylen, &$edits)
    {
        $this->start_block($this->block_header($xbeg, $xlen, $ybeg, $ylen));
        foreach ($edits as $edit) {
            if ($edit->type == 'copy')
                $this->context($edit->orig);
            elseif ($edit->type == 'add')
                $this->added($edit->final);
            elseif ($edit->type == 'delete')
                $this->deleted($edit->orig);
            elseif ($edit->type == 'change')
                $this->changed($edit->orig, $edit->final);
            else
                trigger_error("Unknown edit type", E_USER_ERROR);
        }
        $this->end_block();
    }

    protected function start_diff()
    {
        ob_start();
    }

    protected function end_diff()
    {
        $val = ob_get_contents();
        ob_end_clean();
        return $val;
    }

    protected function block_header($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen > 1)
            $xbeg .= "," . ($xbeg + $xlen - 1);
        if ($ylen > 1)
            $ybeg .= "," . ($ybeg + $ylen - 1);

        return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
    }

    protected function start_block($header)
    {
        echo $header;
    }

    protected function end_block()
    {
    }

    protected function lines($lines, $prefix = ' ')
    {
        foreach ($lines as $line)
            echo "$prefix $line\n";
    }

    protected function context($lines)
    {
        $this->lines($lines);
    }

    protected function added($lines)
    {
        $this->lines($lines, ">");
    }

    protected function deleted($lines)
    {
        $this->lines($lines, "<");
    }

    protected function changed($orig, $final)
    {
        $this->deleted($orig);
        echo "---\n";
        $this->added($final);
    }
}

/**
 * "Unified" diff formatter.
 *
 * This class formats the diff in classic "unified diff" format.
 */
class UnifiedDiffFormatter extends DiffFormatter
{
    function __construct($context_lines = 4)
    {
        $this->leading_context_lines = $context_lines;
        $this->trailing_context_lines = $context_lines;
    }

    protected function block_header($xbeg, $xlen, $ybeg, $ylen)
    {
        if ($xlen != 1)
            $xbeg .= "," . $xlen;
        if ($ylen != 1)
            $ybeg .= "," . $ylen;
        return "@@ -$xbeg +$ybeg @@\n";
    }

    protected function added($lines)
    {
        $this->lines($lines, "+");
    }

    protected function deleted($lines)
    {
        $this->lines($lines, "-");
    }

    protected function changed($orig, $final)
    {
        $this->deleted($orig);
        $this->added($final);
    }
}

/**
 * block conflict diff formatter.
 *
 * This class will format a diff identical to Diff3 (i.e. editpage
 * conflicts), but when there are only two source files. To be used by
 * future enhancements to reloading / upgrading pgsrc.
 *
 * Functional but not finished yet, need to eliminate redundant block
 * suffixes (i.e. "=======" immediately followed by another prefix)
 * see class LoadFileConflictPageEditor
 */
class BlockDiffFormatter extends DiffFormatter
{
    function __construct($context_lines = 4)
    {
        $this->leading_context_lines = $context_lines;
        $this->trailing_context_lines = $context_lines;
    }

    protected function lines($lines, $prefix = '')
    {
        if (!$prefix == '')
            echo "$prefix\n";
        foreach ($lines as $line)
            echo "$line\n";
        if (!$prefix == '')
            echo "$prefix\n";
    }

    protected function added($lines)
    {
        $this->lines($lines, ">>>>>>>");
    }

    protected function deleted($lines)
    {
        $this->lines($lines, "<<<<<<<");
    }

    protected function block_header($xbeg, $xlen, $ybeg, $ylen)
    {
        return "";
    }

    protected function changed($orig, $final)
    {
        $this->deleted($orig);
        $this->added($final);
    }
}
