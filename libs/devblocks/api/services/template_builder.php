<?php
class _DevblocksTemplateBuilder {
	private $_twig = null;
	private $_errors = array();
	
	private function _DevblocksTemplateBuilder() {
		$this->_twig = new Twig_Environment(new Twig_Loader_String(), array(
			'cache' => false,
			'debug' => false,
			'auto_reload' => true,
			'trim_blocks' => true,
			'autoescape' => false,
		));
		
		if(class_exists('_DevblocksTwigExtensions', true)) {
			$this->_twig->addExtension(new _DevblocksTwigExtensions());
		}
	}
	
	/**
	 * 
	 * @return _DevblocksTemplateBuilder
	 */
	static function getInstance() {
		static $instance = null;
		if(null == $instance) {
			$instance = new _DevblocksTemplateBuilder();
		}
		return $instance;
	}

	/**
	 * @return Twig_Environment
	 */
	public function getEngine() {
		return $this->_twig;
	}
	
	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->_errors;
	}
	
	private function _setUp() {
		$this->_errors = array();
	}
	
	private function _tearDown() {
	}
	
	function tokenize($templates) {
		$tokens = array();
		
		if(!is_array($templates))
			$templates = array($templates);

		foreach($templates as $template) {
			try {
				$token_stream = $this->_twig->tokenize($template); /* @var $token_stream Twig_TokenStream */
				$node_stream = $this->_twig->parse($token_stream); /* @var $node_stream Twig_Node_Module */
	
				$visitor = new _DevblocksTwigExpressionVisitor();
				$traverser = new Twig_NodeTraverser($this->_twig);
				$traverser->addVisitor($visitor);
				$traverser->traverse($node_stream);
				
				//var_dump($visitor->getFoundTokens());
				$tokens = array_merge($tokens, $visitor->getFoundTokens());
				
			} catch(Exception $e) {
				//var_dump($e->getMessage());
			}
		}
		
		$tokens = array_unique($tokens); 
		
		return $tokens;
	}
	
	function stripModifiers($array) {
		array_walk($array, array($this,'_stripModifiers'));
		return $array;
	}
	
	function _stripModifiers(&$item, $key) {
		if(false != ($pos = strpos($item, '|'))) {
			$item = substr($item, 0, $pos);
		}
	}
	
	/**
	 * 
	 * @param string $template
	 * @param array $vars
	 * @return string
	 */
	function build($template, $vars) {
		$this->_setUp();
		try {
			$template = $this->_twig->loadTemplate($template); /* @var $template Twig_Template */
			$out = $template->render($vars);
		} catch(Exception $e) {
			$this->_errors[] = $e->getMessage();
		}
		$this->_tearDown();

		if(!empty($this->_errors))
			return false;
		
		return $out;
	} 
};

class _DevblocksTwigExpressionVisitor implements Twig_NodeVisitorInterface {
	protected $_tokens = array();
	
	public function enterNode(Twig_NodeInterface $node, Twig_Environment $env) {
		if($node instanceof Twig_Node_Expression_Name) {
			$this->_tokens[$node->getAttribute('name')] = true;
		}
		return $node;
	}
	
	public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env) {
		return $node;
	}
	
 	function getPriority() {
 		return 0;
 	}
 	
 	function getFoundTokens() {
 		return array_keys($this->_tokens);
 	}
};

if(class_exists('Twig_Extension', true)):
class _DevblocksTwigExtensions extends Twig_Extension {
	public function getName() {
		return 'devblocks_twig';
	}
	
	public function getFilters() {
		return array(
			'regexp' => new Twig_Filter_Method($this, 'filter_regexp'),
			'truncate' => new Twig_Filter_Method($this, 'filter_truncate'),
		);
	}
	
	function filter_regexp($string, $pattern, $group = 0) {
		$matches = array();
		@preg_match($pattern, $string, $matches);
		
		$string = '';
		
		if(is_array($matches) && isset($matches[$group])) {
			$string = $matches[$group];
		}		
		
		return $string;
	}	
	
	/**
	 * https://github.com/fabpot/Twig-extensions/blob/master/lib/Twig/Extensions/Extension/Text.php
	 *  
	 * @param string $value
	 * @param integer $length
	 * @param boolean $preserve
	 * @param string $separator
	 * 
	 */
	function filter_truncate($value, $length = 30, $preserve = false, $separator = '...') {
		if (mb_strlen($value, LANG_CHARSET_CODE) > $length) {
			if ($preserve) {
				if (false !== ($breakpoint = mb_strpos($value, ' ', $length, LANG_CHARSET_CODE))) {
					$length = $breakpoint;
				}
			}
			return mb_substr($value, 0, $length, LANG_CHARSET_CODE) . $separator;
		}
		return $value;
	}	
};
endif;