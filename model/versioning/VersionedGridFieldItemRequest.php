<?php

/**
 * Provides versioned dataobject support to {@see GridFieldDetailForm_ItemRequest}
 *
 * @property GridFieldDetailForm_ItemRequest $owner
 */
class VersionedGridFieldItemRequest extends GridFieldDetailForm_ItemRequest {

    protected function getFormActions() {
        $actions = parent::getFormActions();

		// Check if record is versionable
		$record = $this->getRecord();
        if(!$record || !$record->has_extension('Versioned')) {
            return $actions;
        }

        // Save & Publish action
		if($record->canPublish()) {
			// "publish", as with "save", it supports an alternate state to show when action is needed.
			$publish = FormAction::create(
                'doPublish',
                _t('VersionedGridFieldItemRequest.BUTTONPUBLISH', 'Publish')
            )
                ->setUseButtonTag(true)
                ->addExtraClass('ss-ui-action-constructive')
                ->setAttribute('data-icon', 'accept');

            // Insert after save
            if($actions->fieldByName('action_doSave')) {
                $actions->insertAfter('action_doSave', $publish);
            } else {
                $actions->push($publish);
            }
		}

        // Unpublish action
        $isPublished = $record->isPublished();
		if($isPublished && $record->canUnpublish()) {
			$actions->push(
				FormAction::create(
                    'doUnpublish',
                    _t('VersionedGridFieldItemRequest.BUTTONUNPUBLISH', 'Unpublish')
                )
                    ->setUseButtonTag(true)
					->setDescription(_t(
                        'VersionedGridFieldItemRequest.BUTTONUNPUBLISHDESC',
                        'Remove this record from the published site'
                    ))
					->addExtraClass('ss-ui-action-destructive')
			);
		}

        // Archive action
		if($record->canArchive()) {
            // Replace "delete" action
            $actions->removeByName('action_doDelete');

            // "archive"
            $actions->push(
                FormAction::create('doArchive', _t('VersionedGridFieldItemRequest.ARCHIVE','Archive'))
                    ->setDescription(_t(
                        'VersionedGridFieldItemRequest.BUTTONARCHIVEDESC',
                        'Unpublish and send to archive'
                    ))
                    ->addExtraClass('delete ss-ui-action-destructive')
            );
		}
		return $actions;
    }

    /**
     * Archive this versioned record
     *
     * @param array $data
     * @param Form $form
	 * @return SS_HTTPResponse
     */
	public function doArchive($data, $form) {
		$record = $this->getRecord();
		if (!$record->canArchive()) {
			return $this->httpError(403);
		}

		// Record name before it's deleted
		$title = $record->Title;

		try {
			$record->doArchive();
		} catch(ValidationException $e) {
			return $this->generateValidationResponse($form, $e);
		}

		$message = sprintf(
			_t('VersionedGridFieldItemRequest.Archived', 'Archived %s %s'),
			$record->i18n_singular_name(),
			Convert::raw2xml($title)
		);
		$this->setFormMessage($form, $message);

		//when an item is deleted, redirect to the parent controller
		$controller = $this->getToplevelController();
		$controller->getRequest()->addHeader('X-Pjax', 'Content'); // Force a content refresh

		return $controller->redirect($this->getBacklink(), 302); //redirect back to admin section
    }

    /**
     * Publish this versioned record
     *
     * @param array $data
     * @param Form $form
	 * @return SS_HTTPResponse
     */
    public function doPublish($data, $form) {
		/** @var Versioned|DataObject $record */
		$record = $this->getRecord();
        $isNewRecord = $record->ID == 0;

		// Check permission
		if(!$record->canPublish()) {
			return $this->httpError(403);
		}

		// Save from form data
		try {
            // Initial save and reload
			$record = $this->saveFormIntoRecord($data, $form);
            $record->publishRecursive();

		} catch(ValidationException $e) {
			return $this->generateValidationResponse($form, $e);
		}

		$editURL = $this->Link('edit');
		$xmlTitle = Convert::raw2xml($record->Title);
		$link = "<a href=\"{$editURL}\">{$xmlTitle}</a>";
		$message = _t(
			'VersionedGridFieldItemRequest.Published',
			'Published {name} {link}',
			array(
				'name' => $record->i18n_singular_name(),
				'link' => $link
			)
		);
		$this->setFormMessage($form, $message);

		return $this->redirectAfterSave($isNewRecord);
    }

    /**
     * Delete this record from the live site
     *
     * @param array $data
     * @param Form $form
	 * @return SS_HTTPResponse
     */
    public function doUnpublish($data, $form) {
		$record = $this->getRecord();
		if (!$record->canUnpublish()) {
			return $this->httpError(403);
		}

		// Record name before it's deleted
		$title = $record->Title;

		try {
			$record->doUnpublish();
		} catch(ValidationException $e) {
			return $this->generateValidationResponse($form, $e);
		}

		$message = sprintf(
			_t('VersionedGridFieldItemRequest.Unpublished', 'Unpublished %s %s'),
			$record->i18n_singular_name(),
			Convert::raw2xml($title)
		);
		$this->setFormMessage($form, $message);

		// Redirect back to edit
		return $this->redirectAfterSave(false);
    }

	/**
	 * @param Form $form
	 * @param string $message
	 */
	protected function setFormMessage($form, $message) {
		$form->sessionMessage($message, 'good', false);
		$controller = $this->getToplevelController();
		if($controller->hasMethod('getEditForm')) {
			$backForm = $controller->getEditForm();
			$backForm->sessionMessage($message, 'good', false);
		}
	}
}
