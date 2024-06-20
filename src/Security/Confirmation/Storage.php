<?php

namespace SilverStripe\Security\Confirmation;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Security\SecurityToken;

/**
 * Confirmation Storage implemented on top of SilverStripe Session and Cookie
 *
 * The storage keeps the information about the items requiring
 * confirmation and their status (confirmed or not) in Session
 *
 * User data, such as the original request parameters, may be kept in
 * Cookie so that session storage cannot be exhausted easily by a malicious user
 */
class Storage
{
    const HASH_ALGO = 'sha512';

    /**
     * @var \SilverStripe\Control\Session
     */
    protected $session;

    /**
     * Identifier of the storage within the session
     *
     * @var string
     */
    protected $id;

    /**
     * @param Session $session active session
     * @param string $id Unique storage identifier within the session
     * @param bool $new Cleanup the storage
     */
    public function __construct(Session $session, $id, $new = true)
    {
        $id = trim((string) $id);
        if (!strlen($id ?? '')) {
            throw new \InvalidArgumentException('Storage ID must not be empty');
        }

        $this->session = $session;
        $this->id = $id;

        if ($new) {
            $this->cleanup();
        }
    }

    /**
     * Remove all the data from the storage
     * Cleans up Session and Cookie related to this storage
     */
    public function cleanup()
    {
        Cookie::force_expiry($this->getCookieKey());
        $this->session->clear($this->getNamespace());
    }

    /**
     * Gets user input data (usually POST array), checks all the items in the storage
     * has been confirmed and marks them as such.
     *
     * @param array $data User input to look at for items. Usually POST array
     *
     * @return bool whether all items have been confirmed
     */
    public function confirm($data)
    {
        foreach ($this->getItems() as $item) {
            $key = base64_encode($this->getTokenHash($item) ?? '');

            if (!isset($data[$key]) || $data[$key] !== '1') {
                return false;
            }

            $item->confirm();

            $this->putItem($item);
        }

        return true;
    }

    /**
     * Returns the dictionary with the item hashes
     *
     * The {@see SilverStripe\Security\Confirmation\Storage::confirm} function
     * expects exactly same dictionary as its argument for successful confirmation
     *
     * Keys of the dictionary are salted item token hashes
     * All values are the string "1" constantly
     *
     * @return array
     */
    public function getHashedItems()
    {
        $items = [];

        foreach ($this->getItems() as $item) {
            $hash = base64_encode($this->getTokenHash($item) ?? '');

            $items[$hash] = '1';
        }

        return $items;
    }

    /**
     * Returns salted and hashed version of the item token
     *
     * @param Item $item
     *
     * @return string
     */
    public function getTokenHash(Item $item)
    {
        $token = $item->getToken();
        $salt = $this->getSessionSalt();

        $salted = $salt . $token;

        return hash(static::HASH_ALGO ?? '', $salted ?? '', true);
    }

    /**
     * Returns the unique cookie key generated from the session salt
     *
     * @return string
     */
    public function getCookieKey()
    {
        $salt = $this->getSessionSalt();

        return bin2hex(hash(static::HASH_ALGO ?? '', $salt . 'cookie key', true));
    }

    /**
     * Returns a unique token to use as a CSRF token
     *
     * @return string
     */
    public function getCsrfToken()
    {
        $salt = $this->getSessionSalt();

        return base64_encode(hash(static::HASH_ALGO ?? '', $salt . 'csrf token', true));
    }

    /**
     * Returns the salt generated for the current session
     *
     * @return string
     */
    public function getSessionSalt()
    {
        $key = $this->getNamespace('salt');

        if (!$salt = $this->session->get($key)) {
            $salt = $this->generateSalt();
            $this->session->set($key, $salt);
        }

        return $salt;
    }

    /**
     * Returns randomly generated salt
     *
     * @return string
     */
    protected function generateSalt()
    {
        return random_bytes(64);
    }

    /**
     * Adds a new object to the list of confirmation items
     * Replaces the item if there is already one with the same token
     *
     * @param Item $item Item requiring confirmation
     *
     * @return $this
     */
    public function putItem(Item $item)
    {
        $key = $this->getNamespace('items');

        $items = $this->session->get($key) ?: [];

        $token = $this->getTokenHash($item);
        $items[$token] = $item;
        $this->session->set($key, $items);

        return $this;
    }

    /**
     * Returns the list of registered confirmation items
     *
     * @return Item[]
     */
    public function getItems()
    {
        return $this->session->get($this->getNamespace('items')) ?: [];
    }

    /**
     * Look up an item by its token key
     *
     * @param string $key Item token key
     *
     * @return null|Item
     */
    public function getItem($key)
    {
        foreach ($this->getItems() as $item) {
            if ($item->getToken() === $key) {
                return $item;
            }
        }
    }

    /**
     * This request should be performed on success
     * Usually the original request which triggered the confirmation
     *
     * @param HTTPRequest $request
     *
     * @return $this
     */
    public function setSuccessRequest(HTTPRequest $request)
    {
        $url = Controller::join_links(Director::baseURL(), $request->getURL(true));
        $this->setSuccessUrl($url);

        $httpMethod = $request->httpMethod();
        $this->session->set($this->getNamespace('httpMethod'), $httpMethod);

        if ($httpMethod === 'POST') {
            $checksum = $this->setSuccessPostVars($request->postVars());
            $this->session->set($this->getNamespace('postChecksum'), $checksum);
        }
    }

    /**
     * Save the post data in the storage (browser Cookies by default)
     * Returns the control checksum of the data preserved
     *
     * Keeps data in Cookies to avoid potential DDoS targeting
     * session storage exhaustion
     *
     * @param array $data
     *
     * @return string checksum
     */
    protected function setSuccessPostVars(array $data)
    {
        $checksum = hash_init(static::HASH_ALGO ?? '');
        $cookieData = [];

        foreach ($data as $key => $val) {
            $key = strval($key);
            $val = strval($val);

            hash_update($checksum, $key ?? '');
            hash_update($checksum, $val ?? '');

            $cookieData[] = [$key, $val];
        }

        $checksum = hash_final($checksum, true);
        $cookieData = json_encode($cookieData, 0, 2);

        $cookieKey = $this->getCookieKey();
        Cookie::set($cookieKey, $cookieData, 0);

        return $checksum;
    }

    /**
     * Returns HTTP method of the success request
     *
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->session->get($this->getNamespace('httpMethod'));
    }

    /**
     * Returns the list of success request post parameters
     *
     * Returns null if no parameters was persisted initially or
     * if the checksum is incorrect.
     *
     * WARNING! If HTTP Method is POST and this function returns null,
     * you MUST assume the Cookie parameter either has been forged or
     * expired.
     *
     * @return array|null
     */
    public function getSuccessPostVars()
    {
        $controlChecksum = $this->session->get($this->getNamespace('postChecksum'));

        if (!$controlChecksum) {
            return null;
        }

        $cookieKey = $this->getCookieKey();
        $cookieData = Cookie::get($cookieKey);

        if (!$cookieData) {
            return null;
        }

        $cookieData = json_decode($cookieData ?? '', true, 3);

        if (!is_array($cookieData)) {
            return null;
        }

        $checksum = hash_init(static::HASH_ALGO ?? '');

        $data = [];
        foreach ($cookieData as $pair) {
            if (!isset($pair[0]) || !isset($pair[1])) {
                return null;
            }

            $key = $pair[0];
            $val = $pair[1];

            hash_update($checksum, $key ?? '');
            hash_update($checksum, $val ?? '');

            $data[$key] = $val;
        }

        $checksum = hash_final($checksum, true);

        if ($checksum !== $controlChecksum) {
            return null;
        }

        return $data;
    }

    /**
     * The URL the form should redirect to on success
     *
     * @param string $url Success URL
     *
     * @return $this
     */
    public function setSuccessUrl($url)
    {
        $this->session->set($this->getNamespace('successUrl'), $url);
        return $this;
    }

    /**
     * Returns the URL registered by {@see Storage::setSuccessUrl} as a success redirect target
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->session->get($this->getNamespace('successUrl'));
    }

    /**
     * The URL the form should redirect to on failure
     *
     * @param string $url Failure URL
     *
     * @return $this
     */
    public function setFailureUrl($url)
    {
        $this->session->set($this->getNamespace('failureUrl'), $url);
        return $this;
    }

    /**
     * Returns the URL registered by {@see Storage::setFailureUrl} as a success redirect target
     *
     * @return string
     */
    public function getFailureUrl()
    {
        return $this->session->get($this->getNamespace('failureUrl'));
    }

    /**
     * Check all items to be confirmed in the storage
     *
     * @param Item[] $items List of items to be checked
     *
     * @return bool
     */
    public function check(array $items)
    {
        foreach ($items as $itemToConfirm) {
            foreach ($this->getItems() as $item) {
                if ($item->getToken() !== $itemToConfirm->getToken()) {
                    continue;
                }

                if ($item->isConfirmed()) {
                    continue 2;
                }

                break;
            }

            return false;
        }

        return true;
    }

    /**
     * Returns the namespace of the storage in the session
     *
     * @param string|null $key Optional key within the storage
     *
     * @return string
     */
    protected function getNamespace($key = null)
    {
        return sprintf(
            '%s.%s%s',
            str_replace('\\', '.', __CLASS__),
            $this->id,
            $key ? '.' . $key : ''
        );
    }
}
