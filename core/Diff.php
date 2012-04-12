<?php

/**
 * @package framework
 * @subpackage core
 * A PHP diff engine
 */

// difflib.php
//
// A PHP diff engine for phpwiki.
//
// Copyright (C) 2000, 2001 Geoffrey T. Dairiki <dairiki@dairiki.org>
// You may copy this code freely under the conditions of the GPL.
//

// FIXME: possibly remove assert()'s for production version?

// PHP3 does not have assert()
/**
 */
define('USE_ASSERTS', function_exists('assert'));

/**
 * @package framework
 * @subpackage core
 * @access private
 */
class _DiffOp {
    var $type;
    var $orig;
    var $final;

    function reverse() {
        trigger_error("pure virtual", E_USER_ERROR);
    }

    function norig() {
        return $this->orig ? sizeof($this->orig) : 0;
    }

    function nfinal() {
        return $this->final ? sizeof($this->final) : 0;
    }
}

/**
 * @package framework
 * @subpackage core
 * @access private
 */
class _DiffOp_Copy extends _DiffOp {
    var $type = 'copy';

    function _DiffOp_Copy ($orig, $final = false) {
        if (!is_array($final))
            $final = $orig;
        $this->orig = $orig;
        $this->final = $final;
    }

    function reverse() {
        return new _DiffOp_Copy($this->final, $this->orig);
    }
}

/**
 * @package framework
 * @subpackage core
 * @access private
 */
class _DiffOp_Delete extends _DiffOp {
    var $type = 'delete';

    function _DiffOp_Delete ($lines) {
        $this->orig = $lines;
        $this->final = false;
    }

    function reverse() {
        return new _DiffOp_Add($this->orig);
    }
}

/**
 * @package framework
 * @subpackage core
 * @access private
 */
class _DiffOp_Add extends _DiffOp {
    var $type = 'add';

    function _DiffOp_Add ($lines) {
        $this->final = $lines;
        $this->orig = false;
    }

    function reverse() {
        return new _DiffOp_Delete($this->final);
    }
}

/**
 * @package framework
 * @subpackage core
 * @access private
 */
class _DiffOp_Change extends _DiffOp {
    var $type = 'change';

    function _DiffOp_Change ($orig, $final) {
        $this->orig = $orig;
        $this->final = $final;
    }

    function reverse() {
        return new _DiffOp_Change($this->final, $this->orig);
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
 * @package framework
 * @subpackage core
 */
class _DiffEngine
{
    function diff ($from_lines, $to_lines) {
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
        $xi = $n_from; $yi = $n_to;
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
            if ( ($this->ychanged[$yi] = empty($xhash[$line])) )
                continue;
            $yhash[$line] = 1;
            $this->yv[] = $line;
            $this->yind[] = $yi;
        }
        for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
            $line = $from_lines[$xi];
            if ( ($this->xchanged[$xi] = empty($yhash[$line])) )
                continue;
            $this->xv[] = $line;
            $this->xind[] = $xi;
        }

        // Find the LCS.
        $this->_compareseq(0, sizeof($this->xv), 0, sizeof($this->yv));

        // Merge edits when possible
        $this->_shift_boundaries($from_lines, $this->xchanged, $this->ychanged);
        $this->_shift_boundaries($to_lines, $this->ychanged, $this->xchanged);

        // Compute the edit operations.
        $edits = array();
        $xi = $yi = 0;
        while ($xi < $n_from || $yi < $n_to) {
            USE_ASSERTS && assert($yi < $n_to || $this->xchanged[$xi]);
            USE_ASSERTS && assert($xi < $n_from || $this->ychanged[$yi]);

            // Skip matching "snake".
            $copy = array();
            while ( $xi < $n_from && $yi < $n_to
                    && !$this->xchanged[$xi] && !$this->ychanged[$yi]) {
                $copy[] = $from_lines[$xi++];
                ++$yi;
            }
            if ($copy)
                $edits[] = new _DiffOp_Copy($copy);

            // Find deletes & adds.
            $delete = array();
            while ($xi < $n_from && $this->xchanged[$xi])
                $delete[] = $from_lines[$xi++];

            $add = array();
            while ($yi < $n_to && $this->ychanged[$yi])
                $add[] = $to_lines[$yi++];

            if ($delete && $add)
                $edits[] = new _DiffOp_Change($delete, $add);
            elseif ($delete)
                $edits[] = new _DiffOp_Delete($delete);
            elseif ($add)
                $edits[] = new _DiffOp_Add($add);
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
    function _diag ($xoff, $xlim, $yoff, $ylim, $nchunks) {
	$flip = false;
	
	if ($xlim - $xoff > $ylim - $yoff) {
	    // Things seems faster (I'm not sure I understand why)
            // when the shortest sequence in X.
            $flip = true;
	    list ($xoff, $xlim, $yoff, $ylim)
		= array( $yoff, $ylim, $xoff, $xlim);
        }

	if ($flip)
	    for ($i = $ylim - 1; $i >= $yoff; $i--)
		$ymatches[$this->xv[$i]][] = $i;
	else
	    for ($i = $ylim - 1; $i >= $yoff; $i--)
		$ymatches[$this->yv[$i]][] = $i;

	$this->lcs = 0;
	$this->seq[0]= $yoff - 1;
	$this->in_seq = array();
	$ymids[0] = array();

	$numer = $xlim - $xoff + $nchunks - 1;
	$x = $xoff;
	for ($chunk = 0; $chunk < $nchunks; $chunk++) {
	    if ($chunk > 0)
		for ($i = 0; $i <= $this->lcs; $i++)
		    $ymids[$i][$chunk-1] = $this->seq[$i];

	    $x1 = $xoff + (int)(($numer + ($xlim-$xoff)*$chunk) / $nchunks);
	    for ( ; $x < $x1; $x++) {
                $line = $flip ? $this->yv[$x] : $this->xv[$x];
                if (empty($ymatches[$line]))
		    continue;
		$matches = $ymatches[$line];
                reset($matches);
		while (list ($junk, $y) = each($matches))
		    if (empty($this->in_seq[$y])) {
			$k = $this->_lcs_pos($y);
			USE_ASSERTS && assert($k > 0);
			$ymids[$k] = $ymids[$k-1];
			break;
                    }
		while (list ($junk, $y) = each($matches)) {
		    if ($y > $this->seq[$k-1]) {
			USE_ASSERTS && assert($y < $this->seq[$k]);
			// Optimization: this is a common case:
			//  next match is just replacing previous match.
			$this->in_seq[$this->seq[$k]] = false;
			$this->seq[$k] = $y;
			$this->in_seq[$y] = 1;
                    }
		    else if (empty($this->in_seq[$y])) {
			$k = $this->_lcs_pos($y);
			USE_ASSERTS && assert($k > 0);
			$ymids[$k] = $ymids[$k-1];
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

    function _lcs_pos ($ypos) {
	$end = $this->lcs;
	if ($end == 0 || $ypos > $this->seq[$end]) {
	    $this->seq[++$this->lcs] = $ypos;
	    $this->in_seq[$ypos] = 1;
	    return $this->lcs;
        }

	$beg = 1;
	while ($beg < $end) {
	    $mid = (int)(($beg + $end) / 2);
	    if ( $ypos > $this->seq[$mid] )
		$beg = $mid + 1;
	    else
		$end = $mid;
        }

	USE_ASSERTS && assert($ypos != $this->seq[$end]);

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
    function _compareseq ($xoff, $xlim, $yoff, $ylim) {
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
		= $this->_diag($xoff,$xlim,$yoff, $ylim,$nchunks);
        }

	if ($lcs == 0) {
	    // X and Y sequences have no common subsequence:
	    // mark all changed.
	    while ($yoff < $ylim)
		$this->ychanged[$this->yind[$yoff++]] = 1;
	    while ($xoff < $xlim)
		$this->xchanged[$this->xind[$xoff++]] = 1;
        }
	else {
	    // Use the partitions to split this problem into subproblems.
	    reset($seps);
	    $pt1 = $seps[0];
	    while ($pt2 = next($seps)) {
		$this->_compareseq ($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
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
     * 'compareseq' usually chooses the one at the beginning,
     * but usually it is cleaner to consider the following identical line
     * to be the "change".
     *
     * This is extracted verbatim from analyze.c (GNU diffutils-2.7).
     */
    function _shift_boundaries ($lines, &$changed, $other_changed) {
	$i = 0;
	$j = 0;

	USE_ASSERTS && assert('sizeof($lines) == sizeof($changed)');
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
	
	    while ($i < $len && ! $changed[$i]) {
		USE_ASSERTS && assert('$j < $other_len && ! $other_changed[$j]');
		$i++; $j++;
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
		    USE_ASSERTS && assert('$j > 0');
		    while ($other_changed[--$j])
			continue;
		    USE_ASSERTS && assert('$j >= 0 && !$other_changed[$j]');
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

		    USE_ASSERTS && assert('$j < $other_len && ! $other_changed[$j]');
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
		USE_ASSERTS && assert('$j > 0');
		while ($other_changed[--$j])
		    continue;
		USE_ASSERTS && assert('$j >= 0 && !$other_changed[$j]');
            }
        }
    }
}

/**
 * Class representing a 'diff' between two sequences of strings.
 * @package framework
 * @subpackage core
 */
class Diff
{
	public static $html_cleaner_class = null;

    var $edits;

    /**
     * Constructor.
     * Computes diff between sequences of strings.
     *
     * @param $from_lines array An array of strings.
     *        (Typically these are lines from a file.)
     * @param $to_lines array An array of strings.
     */
    function Diff($from_lines, $to_lines) {
        $eng = new _DiffEngine;
        $this->edits = $eng->diff($from_lines, $to_lines);
        //$this->_check($from_lines, $to_lines);
    }

    /**
     * Compute reversed Diff.
     *
     * SYNOPSIS:
     *
     *  $diff = new Diff($lines1, $lines2);
     *  $rev = $diff->reverse();
     * @return object A Diff object representing the inverse of the
     *                original diff.
     */
    function reverse () {
	$rev = $this;
        $rev->edits = array();
        foreach ($this->edits as $edit) {
            $rev->edits[] = $edit->reverse();
        }
	return $rev;
    }

    /**
     * Check for empty diff.
     *
     * @return bool True iff two sequences were identical.
     */
    function isEmpty () {
        foreach ($this->edits as $edit) {
            if ($edit->type != 'copy')
                return false;
        }
        return true;
    }

    /**
     * Compute the length of the Longest Common Subsequence (LCS).
     *
     * This is mostly for diagnostic purposed.
     *
     * @return int The length of the LCS.
     */
    function lcs () {
	$lcs = 0;
        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy')
                $lcs += sizeof($edit->orig);
        }
	return $lcs;
    }

    /**
     * Get the original set of lines.
     *
     * This reconstructs the $from_lines parameter passed to the
     * constructor.
     *
     * @return array The original sequence of strings.
     */
    function orig() {
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
    function finaltext() {
        $lines = array();

        foreach ($this->edits as $edit) {
            if ($edit->final)
                array_splice($lines, sizeof($lines), 0, $edit->final);
        }
        return $lines;
    }

    /**
     * Check a Diff for validity.
     *
     * This is here only for debugging purposes.
     */
    function _check ($from_lines, $to_lines) {
        if (serialize($from_lines) != serialize($this->orig()))
            trigger_error("Reconstructed original doesn't match", E_USER_ERROR);
        if (serialize($to_lines) != serialize($this->finaltext()))
            trigger_error("Reconstructed final doesn't match", E_USER_ERROR);

        $rev = $this->reverse();
        if (serialize($to_lines) != serialize($rev->orig()))
            trigger_error("Reversed original doesn't match", E_USER_ERROR);
        if (serialize($from_lines) != serialize($rev->finaltext()))
            trigger_error("Reversed final doesn't match", E_USER_ERROR);


        $prevtype = 'none';
        foreach ($this->edits as $edit) {
            if ( $prevtype == $edit->type )
                trigger_error("Edit sequence is non-optimal", E_USER_ERROR);
            $prevtype = $edit->type;
        }

        $lcs = $this->lcs();
        trigger_error("Diff okay: LCS = $lcs", E_USER_NOTICE);
    }
    
 
 
 	/**
	 *  Attempt to clean invalid HTML, which messes up diffs.
	 *  This cleans code if possible, using an instance of HTMLCleaner
	 *
	 *  NB: By default, only extremely simple tidying is performed,
	 *  by passing through DomDocument::loadHTML and saveXML
	 *
	 * @param string $content HTML content
	 * @param object $cleaner Optional instance of a HTMLCleaner class to
	 * 	use, overriding self::$html_cleaner_class
	 */
	static function cleanHTML($content, $cleaner=null) {
		if (!$cleaner) {
			if (class_exists(self::$html_cleaner_class)) {
				$cleaner = new self::$html_cleaner_class;
			} else {
				$cleaner = HTMLCleaner::inst();    //load cleaner if the dependent class is available
			}
		}

		if ($cleaner) {
			$content = $cleaner->cleanHTML($content);
		} else {
			// At most basic level of cleaning, use DOMDocument to save valid XML.
			$doc = new SS_HTMLValue($content);
			$content = $doc->getContent();
		}

		// Remove empty <ins /> and <del /> tags because browsers hate them
		$content = preg_replace('/<(ins|del)[^>]*\/>/','', $content);

		return $content;
	}

	/**
	 * @param String
	 * @param String
	 * @param Boolean
	 * @return String
	 */
	static function compareHTML($from, $to, $escape = false) {
		// First split up the content into words and tags
		$set1 = self::getHTMLChunks($from);
		$set2 = self::getHTMLChunks($to);

		// Diff that
		$diff = new Diff($set1, $set2);

		$tagStack[1] = $tagStack[2] = 0;
		$rechunked[1] = $rechunked[2] = array();
		
		// Go through everything, converting edited tags (and their content) into single chunks.  Otherwise
		// the generated HTML gets crusty
		foreach($diff->edits as $edit) {
			switch($edit->type) {
				case 'copy':
					$lookForTag = false;
					$stuffFor[1] = $edit->orig;
					$stuffFor[2] = $edit->orig;
					break;
				
				case 'change':
					$lookForTag = true;
					$stuffFor[1] = $edit->orig;
					$stuffFor[2] = $edit->final;
					break;
				
				case 'add':
					$lookForTag = true;
					$stuffFor[1] = null;
					$stuffFor[2] = $edit->final;
					break;
				
				case 'delete':
					$lookForTag = true;
					$stuffFor[1] = $edit->orig;
					$stuffFor[2] = null;
					break;
			}
			
			foreach($stuffFor as $listName => $chunks) {
				if($chunks) {
					foreach($chunks as $item) {
						// $tagStack > 0 indicates that we should be tag-building
						if($tagStack[$listName]) $rechunked[$listName][sizeof($rechunked[$listName])-1] .= ' ' . $item;
						else $rechunked[$listName][] = $item;
	
						if($lookForTag && !$tagStack[$listName] && isset($item[0]) && $item[0] == "<" && substr($item,0,2) != "</") { 
							$tagStack[$listName] = 1;
						} else if($tagStack[$listName]) {
							if(substr($item,0,2) == "</") $tagStack[$listName]--;
							else if(isset($item[0]) && $item[0] == "<") $tagStack[$listName]++;
						}
					}
				}
			}
		}
		
		// Diff the re-chunked data, turning it into maked up HTML
		$diff = new Diff($rechunked[1], $rechunked[2]);
		$content = '';
		foreach($diff->edits as $edit) {
			$orig = ($escape) ? Convert::raw2xml($edit->orig) : $edit->orig;
			$final = ($escape) ? Convert::raw2xml($edit->final) : $edit->final;

			switch($edit->type) {
				case 'copy':
					$content .= " " . implode(" ", $orig) . " ";
					break;
				
				case 'change':
					$content .= " <ins>" . implode(" ", $final) . "</ins> ";
					$content .= " <del>" . implode(" ", $orig) . "</del> ";
					break;
				
				case 'add':
					$content .= " <ins>" . implode(" ", $final) . "</ins> ";
					break;
				
				case 'delete':
					$content .= " <del>" . implode(" ", $orig) . "</del> ";
					break;
			}
		}		

		return self::cleanHTML($content);
	}
	
	/**
	 * @param string|array If passed as an array, values will be concatenated with a comma.
	 */
	static function getHTMLChunks($content) {
		if($content && !is_string($content) && !is_array($content) && !is_numeric($content)) {
			throw new InvalidArgumentException('$content parameter needs to be a string or array');
		}
		if(is_array($content)) $content = implode(',', $content);
		
		$content = str_replace(array("&nbsp;","<", ">"),array(" "," <", "> "),$content);
		$candidateChunks = preg_split("/[\t\r\n ]+/", $content);
		while(list($i,$item) = each($candidateChunks)) {
			if(isset($item[0]) && $item[0] == "<") {
				$newChunk = $item;
				while($item[strlen($item)-1] != ">") {
					list($i,$item) = each($candidateChunks);
					$newChunk .= ' ' . $item;
				}
				$chunks[] = $newChunk;
			} else {
				$chunks[] = $item;
			}
		}
		return $chunks;
	}
	
}




/**
 * Computes diff between sequences of strings.
 * @package framework
 * @subpackage core
 */
class MappedDiff
extends Diff
{
    /**
     * Constructor.
     *
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
    function MappedDiff($from_lines, $to_lines,
                        $mapped_from_lines, $mapped_to_lines) {

        assert(sizeof($from_lines) == sizeof($mapped_from_lines));
        assert(sizeof($to_lines) == sizeof($mapped_to_lines));

        $this->Diff($mapped_from_lines, $mapped_to_lines);

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


