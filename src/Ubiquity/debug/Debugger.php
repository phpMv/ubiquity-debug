<?php
namespace Ubiquity\debug;


use Ubiquity\cache\ClassUtils;
use Ubiquity\controllers\admin\popo\ComposerDependency;
use Ubiquity\controllers\Router;
use Ubiquity\controllers\Startup;
use Ubiquity\core\Framework;
use Ubiquity\debug\core\TypeError;
use Ubiquity\utils\base\UIntrospection;
use Ubiquity\utils\base\UString;
use Ubiquity\utils\http\URequest;
use Ubiquity\utils\http\UResponse;

/**
 * Ubiquity debug class.
 * Ubiquity\debug$Debugger
 * This class is part of Ubiquity
 * @author jc
 * @version 1.0.0
 *
 */
class Debugger {
	const CONTEXT_VARIABLES=['globals'=>['_SESSION','_POST','_GET','_REQUEST','_SERVER','_COOKIE','_FILES','_ENV'],'toRemove'=>['e','config','sConfig']];
	private static $variables=[];

	/**
	 * Start the debugger.
	 */
	public static function start(int $level=E_ALL){
		\ob_start(array(
			__class__,
			'_error_handler'
		));
		self::setErrorLevel($level);
	}
	
	public static function setErrorLevel(int $level=E_ALL){
		\error_reporting($level);
		if($level>0){
			\ini_set('display_errors', '1');
		}
	}

	public static function _error_handler($buffer) {
		$e = \error_get_last();
		if ($e) {
			if ($e['file'] != 'xdebug://debug-eval' && ! UResponse::isJSON()) {
				$file=$e['file'];
				$code=self::getFileContent($file);
				$error=$e['message'];
				$type=TypeError::asString($e['type']);
				$line=$e['line'];
				$message=self::loadView('error',['file'=>$file,'error'=>$error,'code'=>$code,'line'=>$line,'type'=>$type]);
				switch ($e['type']) {
					case E_ERROR: case E_PARSE:case E_COMPILE_ERROR:case E_WARNING:
						return self::getErrorFromValues($file,$line,$error,$type,'',false);

					default:
						return self::wrapResponse(\str_replace($e['message'], "", $buffer).$message );
				}
			} else {
				return self::wrapResponse(\str_replace($e['message']??'', "", $buffer));
			}
		}
		return $buffer;
	}

	public static function showException(\Error|\Exception $e){
		$file=$e->getFile();
		$line=$e->getLine();
		$traces=$e->getTrace();
		echo self::getErrorFromValues($file,$line,$e->getMessage(),$e,self::showTraces($traces),true);
	}

	private static function showTraces($traces){
		self::$variables=[];
		$tracesContent='';
		foreach ($traces as $index=>$trace){
			$tracesContent.=self::showTrace($trace,$index);
		}
		if($tracesContent!=null) {
			return self::loadView('traces', ['content' => $tracesContent,'variables'=>json_encode(self::$variables),'count'=>\count($traces)]);
		}
		return '';

	}

	private static function getGlobales($variables){
		$result=[];
		foreach (self::CONTEXT_VARIABLES['globals'] as $k){
			$result[$k]=$variables[$k];
		}
		return $result;
	}

	private static function getLocales(){
		$variables=[];
		$variables['Request']['controller']=Startup::getController();
		$variables['Request']['action']=Startup::getAction();
		$variables['Request']['params']=Startup::getActionParams();
		$variables['Request']['method']=URequest::getMethod();
		$path=Framework::getUrl();
		$variables['Request']['url']=$path;
		$variables['Route']=Router::getRouteInfo($path);
		$variables['Application']['cacheSystem']=Framework::getCacheSystem();
		$variables['Application']['AnnotationsEngine']=Framework::getAnnotationsEngine();
		$variables['Application']['applicationDir']=Startup::getApplicationDir();
		$variables['Application']['Ubiquity-version']=Framework::getVersion();
		return $variables;
	}
	
	private static function showAllVariables(){
		$l=self::getLocales();
		$g=self::getGlobales($GLOBALS);
		$locales=self::showVariables($l);
		$globales=self::showVariables($g);
		return self::loadView('all_variables',compact('locales','globales'));
	}

	private static function showVariables($variables){
		$keys=array_keys($variables);
		$names='';
		$variables_elements='';
		foreach ($keys as $i=>$k){
			$active='';
			$v=$variables[$k];
			if(\is_array($v)){
				$ve=self::showVariable($k,$v,1);
			}else{
				$ve="<span class='ui label'>".\var_export($v,true)."</span>";
			}
			if($i===0){
				$first_var="<div class='variable'>$ve</div>";
				$active='active';
			}
			$names.="<a class='item display_var $active' data-id='$k'>$k</a>";
			$variables_elements.="<div id='ve-$k' class='variable'>$ve</div>";
		}
		return self::loadView('menu_variables',compact('names','variables_elements','first_var'));
	}

	private static function showVariable($name,array $variables,$level=1){
		$values='';
		$count=\count($variables);
		if($count>0) {
			foreach ($variables as $k => $v) {
				if($v!=$variables) {
					if (is_array($v)) {
						$v = self::showVariable($k, $v, $level + 1);
					} else {
						$v = '<span class="ui label">' . var_export($v, true) . '</span>';
					}
					$values .= "<tr><td><b>$k</b></td><td>" . $v . "</td></tr>";
				}
			}
			return self::loadView('variable', ['level' => $level, 'variables' => $values, 'name' => $name,'count'=>$count]);
		}
		return '<span class="ui label">empty</span>';
	}

	private static function showTrace($trace,$index){
		$callFunction=$trace['function']??'';
		$callMethod=null;
		$line=$trace['line']??0;
		$callClass=$trace['class']??'no class';
		$args=$trace['args']??[];
		$file=$trace['file'];
		$attr=UString::cleanAttribute($callClass.".".$callFunction);
		self::$variables[$attr]=[];
		if($file!=null) {
			$class = ClassUtils::getClassFullNameFromFile($file);
			if ($class != null && $class != '\\' && (\class_exists($class) || \trait_exists($class))) {
				$method = UIntrospection::getMethodAtLine($class, $line);
				if ($callClass !== 'no class') {
					$callMethod = new \ReflectionMethod($callClass, $callFunction);
				}
				if ($callMethod != null) {
					$code = UIntrospection::getMethodCode($method, file($trace['file']));

					$attributes = $callMethod->getParameters();
					$effectiveArguments=self::getCallbackArguments($file,$line,$callFunction);
					foreach ($attributes as $i => $param) {
						$arg=$effectiveArguments[$i]??('$'.$param->getName());
						self::$variables[$attr]['$'.$param->getName()] = ['name'=>$effectiveArguments[$i]??'','value'=>var_export($args[$i] ?? '', true)];
						$code=str_replace($arg,"<mark>$arg</mark>",$code);
					}
					$start = $method->getStartLine();
					$countParams = count(self::$variables[$attr] ?? []);
					$parameters = '';
					if ($countParams > 0) {
						$parameters = self::loadView('parameters', ['count' => $countParams, 'variables' => self::getMethodParametersTable($attr)]);
					}
					return self::loadView('trace', [
						'in' => $method->getName(),
						'function' => $callFunction,
						'line' => $line,
						'class' => $class,
						'code' => $code,
						'start' => $start,
						'active' => '',
						'attr' => $attr,
						'parameters' => $parameters,
						'index' => $index
					]);
				}
			}
			$code = self::getFileContent($file);
			$start = 1;
			return self::loadView('trace', ['in' => $file, 'function' => $callFunction, 'line' => $line, 'class' => '', 'code' => $code, 'start' => $start, 'active' => '', 'attr' => $attr, 'parameters' => '', 'index' => $index]);
		}
		return '';
	}

	private static function getCallbackArguments($file,$lineNumber,$callbackName){
		$fileContent=\file($file);
		return UIntrospection::getMethodEffectiveParameters('<?php '.$fileContent[$lineNumber-1],$callbackName);
	}

	private static function getMethodParametersTable($functionName){
		$res='';
		if(isset(self::$variables[$functionName]) && \is_array(self::$variables[$functionName])) {
			foreach (self::$variables[$functionName] as $name => $value) {
				$res .= "<tr><td><b>$name</b></td><td><pre>".$value['value']."</pre></td></tr>";
			}
		}
		return $res;
	}

	private static function getErrorFromValues($file,$line,$errorMessage,$errorType,$traces='',$introspect=true){
		$code=null;
		$vars = self::showAllVariables();
		$controller_action=\basename($file);
		if($introspect) {
			$class = ClassUtils::getClassFullNameFromFile($file, true);
			if($class!==null) {
				$controller_action = $class;
				$method = UIntrospection::getMethodAtLine($class, $line);
				if ($method != null) {
					$start = $method->getStartLine();
					$code = UIntrospection::getMethodCode($method, file($file));
					$controller_action .= '::' . $method->getName();
				}
			}
		}
		if($code==null) {
			$start=1;
			$code = self::getFileContent($file);
		}
		$type=TypeError::asString($errorType);
		$message=self::loadView('error',['file'=>$file,'error'=>$errorMessage,'code'=>$code,'line'=>$line,'type'=>$type,'start'=>$start,'traces'=>$traces,'vars'=>$vars,'controller_action'=>$controller_action]);
		return self::wrapResponse($message);
	}

	private static function wrapResponse($content){
		return self::getHeader().$content.self::getFooter();
	}

	private static function getHeader(){
		return '<div class="ui container">';
	}

	private static function getFooter(){
		return '</div>'.'<script type="text/javascript">'.\file_get_contents(__DIR__.'/js/loader.js').'</script>';
	}

	private static function getFileContent($file){
		return \htmlentities(\file_get_contents($file));
	}

	private static function loadView($name,$data){
		$content=\file_get_contents(__DIR__.'/views/'.$name.'.html');
		foreach ($data as $key=>$value){
			$content=str_replace('{{'.$key.'}}',$value,$content);
		}
		return $content;
	}
}