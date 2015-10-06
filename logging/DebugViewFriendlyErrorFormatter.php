<?php

namespace SilverStripe\Framework\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;

/**
 * Produce a friendly error message
 */
class DebugViewFriendlyErrorFormatter implements FormatterInterface
{

	protected $statusCode = 500;
	protected $friendlyErrorMessage = 'Error';
	protected $friendlyErrorDetail;

	public function getStatusCode() {
		return $this->statusCode;
	}

	public function setStatusCode($statusCode) {
		$this->statusCode = $statusCode;
	}

	public function getTitle($title) {
		return $this->friendlyErrorMessage;
	}

	public function setTitle($title) {
		$this->friendlyErrorMessage = $title;
	}

	public function getBody($title) {
		return $this->friendlyErrorDetail;
	}

	public function setBody($body) {
		$this->friendlyErrorDetail = $body;
	}

	public function format(array $record)
	{

		return $this->output();
	}

	public function formatBatch(array $records) {
		return $this->output();
	}

	public function output() {
		// TODO: Refactor into a content-type option
		if(\Director::is_ajax()) {
			return $this->friendlyErrorMessage;

		} else {
			// TODO: Refactor this into CMS
			if(class_exists('ErrorPage')){
				$errorFilePath = \ErrorPage::get_filepath_for_errorcode(
					$this->statusCode,
					class_exists('Translatable') ? \Translatable::get_current_locale() : null
				);

				if(file_exists($errorFilePath)) {
					$content = file_get_contents($errorFilePath);
					if(!headers_sent()) {
						header('Content-Type: text/html');
					}
					// $BaseURL is left dynamic in error-###.html, so that multi-domain sites don't get broken
					return str_replace('$BaseURL', \Director::absoluteBaseURL(), $content);
				}
			}


			$renderer = \Debug::create_debug_view();
			$output = $renderer->renderHeader();
			$output .= $renderer->renderInfo("Website Error", $this->friendlyErrorMessage, $this->friendlyErrorDetail);

			if(\Email::config()->admin_email) {
				$mailto = \Email::obfuscate(\Email::config()->admin_email);
				$output .= $renderer->renderParagraph('Contact an administrator: ' . $mailto . '');
			}

			$output .= $renderer->renderFooter();
			return $output;
		}
	}
}
