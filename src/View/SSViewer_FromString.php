<?php

namespace SilverStripe\View;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;

/**
 * Special SSViewer that will process a template passed as a string, rather than a filename.
 * @deprecated 5.4.0 Will be replaced with SilverStripe\View\SSTemplateEngine::renderString()
 */
class SSViewer_FromString extends SSViewer
{
    /**
     * The global template caching behaviour if no instance override is specified
     *
     * @config
     * @var bool
     */
    private static $cache_template = true;

    /**
     * The template to use
     *
     * @var string
     */
    protected $content;

    /**
     * Indicates whether templates should be cached
     *
     * @var bool
     */
    protected $cacheTemplate;

    /**
     * @param string $content
     * @param TemplateParser $parser
     */
    public function __construct($content, TemplateParser $parser = null)
    {
        Deprecation::noticeWithNoReplacment(
            '5.4.0',
            'Will be replaced with SilverStripe\View\SSTemplateEngine::renderString()',
            Deprecation::SCOPE_CLASS
        );
        if ($parser) {
            $this->setParser($parser);
        }

        $this->content = $content;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item, $arguments = null, $scope = null)
    {
        $hash = sha1($this->content ?? '');
        $cacheFile = TEMP_PATH . DIRECTORY_SEPARATOR . ".cache.$hash";

        if (!file_exists($cacheFile ?? '') || Injector::inst()->get(Kernel::class)->isFlushed()) {
            $content = $this->parseTemplateContent($this->content, "string sha1=$hash");
            $fh = fopen($cacheFile ?? '', 'w');
            fwrite($fh, $content ?? '');
            fclose($fh);
        }

        $val = $this->includeGeneratedTemplate($cacheFile, $item, $arguments, null, $scope);

        if ($this->cacheTemplate !== null) {
            $cacheTemplate = $this->cacheTemplate;
        } else {
            $cacheTemplate = static::config()->get('cache_template');
        }

        if (!$cacheTemplate) {
            unlink($cacheFile ?? '');
        }

        $html = DBField::create_field('HTMLFragment', $val);

        return $html;
    }

    /**
     * @param boolean $cacheTemplate
     */
    public function setCacheTemplate($cacheTemplate)
    {
        $this->cacheTemplate = (bool)$cacheTemplate;
    }

    /**
     * @return boolean
     */
    public function getCacheTemplate()
    {
        return $this->cacheTemplate;
    }
}
