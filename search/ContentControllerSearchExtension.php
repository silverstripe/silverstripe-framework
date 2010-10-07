<?php
/**
 * Extension to provide a search interface when applied to ContentController
 *
 * @package sapphire
 * @subpackage search
 */
class ContentControllerSearchExtension extends Extension {
	static $allowed_actions = array(
		'SearchForm',
		'results',
	);

	/**
	 * Site search form
	 */
	function SearchForm() {
		$searchText =  _t('SearchForm.SEARCH', 'Search');

		if($this->owner->request) {
			$searchText = $this->owner->request->getVar('Search');
		}

		$fields = new FieldSet(
			new TextField('Search', false, $searchText)
		);
		$actions = new FieldSet(
			new FormAction('results', _t('SearchForm.GO', 'Go'))
		);
		$form = new SearchForm($this->owner, 'SearchForm', $fields, $actions);
		$form->classesToSearch(FulltextSearchable::get_searchable_classes());
		return $form;
	}

	/**
	 * Process and render search results.
	 *
	 * @param array $data The raw request data submitted by user
	 * @param SearchForm $form The form instance that was submitted
	 * @param SS_HTTPRequest $request Request generated for this action
	 */
	function results($data, $form, $request) {
		$data = array(
			'Results' => $form->getResults(),
			'Query' => $form->getSearchQuery(),
			'Title' => _t('SearchForm.SearchResults', 'Search Results')
		);
		return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
	}
}