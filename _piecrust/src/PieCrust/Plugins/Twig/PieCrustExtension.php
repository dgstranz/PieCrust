<?php

require_once 'PieCrustFormatterTokenParser.php';

use PieCrust\IPieCrust;
use PieCrust\Environment\LinkCollector;
use PieCrust\Util\PieCrustHelper;
use PieCrust\Util\UriBuilder;


class PieCrustExtension extends Twig_Extension
{
    protected $pieCrust;
    protected $linkCollector;
    protected $defaultBlogKey;
    protected $postUrlFormat;
    protected $tagUrlFormat;
    protected $categoryUrlFormat;
    
    public function __construct(IPieCrust $pieCrust)
    {
        $this->pieCrust = $pieCrust;
        $this->linkCollector = $pieCrust->getEnvironment()->getLinkCollector();
        
        $blogKeys = $pieCrust->getConfig()->getValueUnchecked('site/blogs');
        $this->defaultBlogKey = $blogKeys[0];
        $this->postUrlFormat = $pieCrust->getConfig()->getValueUnchecked($blogKeys[0].'/post_url');
        $this->tagUrlFormat = $pieCrust->getConfig()->getValueUnchecked($blogKeys[0].'/tag_url');
        $this->categoryUrlFormat = $pieCrust->getConfig()->getValueUnchecked($blogKeys[0].'/category_url');
    }
    
    public function getName()
    {
        return "piecrust";
    }
    
    public function getTokenParsers()
    {
        return array(
            new PieCrustFormatterTokenParser(),
        );
    }
    
    public function getFunctions()
    {
        return array(
            'pcurl'    => new Twig_Function_Method($this, 'getUrl'),
            'pcposturl' => new Twig_Function_Method($this, 'getPostUrl'),
            'pctagurl' => new Twig_Function_Method($this, 'getTagUrl'),
            'pccaturl' => new Twig_Function_Method($this, 'getCategoryUrl'),
            'wordcount' => new Twig_Function_Method($this, 'getWordCount')
        );
    }
    
    public function getFilters()
    {
        return array(
            'nocache' => new Twig_Filter_Method($this, 'addNoCacheParameter'),
            'formatwith' => new Twig_Filter_Method($this, 'transformGeneric'),
            'markdown' => new Twig_Filter_Method($this, 'transformMarkdown'),
            'textile' => new Twig_Filter_Method($this, 'transformTextile')
        );
    }
    
    public function getUrl($value)
    {
        return PieCrustHelper::formatUri($this->pieCrust, $value);
    }
    
    public function getPostUrl($year, $month, $day, $slug, $blogKey = null)
    {
        $postInfo = array(
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'name' => $slug
        );
        $format = ($blogKey == null) ? $this->postUrlFormat : $pieCrust->getConfig()->getValueUnchecked($blogKey.'/post_url');
        return PieCrustHelper::formatUri($this->pieCrust, UriBuilder::buildPostUri($format, $postInfo));
    }
    
    public function getTagUrl($value, $blogKey = null)
    {
        if ($this->linkCollector)
            $this->linkCollector->registerTagCombination($blogKey == null ? $this->defaultBlogKey : $blogKey, $value);

        $format = ($blogKey == null) ? $this->tagUrlFormat : $pieCrust->getConfig()->getValueUnchecked($blogKey.'/tag_url');
        return PieCrustHelper::formatUri($this->pieCrust, UriBuilder::buildTagUri($format, $value));
    }
    
    public function getCategoryUrl($value, $blogKey = null)
    {
        $format = ($blogKey == null) ? $this->categoryUrlFormat : $pieCrust->getConfig()->getValueUnchecked($blogKey.'/category_url');
        return PieCrustHelper::formatUri($this->pieCrust, UriBuilder::buildCategoryUri($format, $value));
    }

    public function getWordCount($value)
    {
        $words = explode(" ", $value);
        return count($words);
    }

    public function addNoCacheParameter($value, $parameterName = 't', $parameterValue = null)
    {
        if (!$parameterValue)
            $parameterValue = time();

        if (strpos($value, '?') === false)
            $value .= '?';
        else
            $value .= '&';
        $value .= $parameterName . '=' . $parameterValue;

        return $value;
    }

    public function transformGeneric($value, $formatterName = null)
    {
        return PieCrustHelper::formatText($this->pieCrust, $value, $formatterName);
    }

    public function transformMarkdown($value)
    {
        return $this->transformGeneric($value, 'markdown');
    }

    public function transformTextile($value)
    {
        return $this->transformGeneric($value, 'textile');
    }
}
