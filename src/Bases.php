<?php
namespace FoxORM;
use FoxORM\Validate\Validate;
use InvalidArgumentException;
class Bases implements \ArrayAccess{
	private $map;
	private $mapObjects= [];
	private $modelClassPrefix;
	private $entityClassDefault;
	private $entityFactory;
	private $tableWrapperFactory;
	private $primaryKeyDefault;
	private $uniqTextKeyDefault;
	private $primaryKeys;
	private $uniqTextKeys;
	private $many2manyPrefix;
	private $tableWrapperClassDefault;
	private $integerSuffixAsPolymorphism;
	private $debug;
	private $validateService;
	function __construct(array $map = [],$modelClassPrefix='Model\\',$entityClassDefault='stdClass',$primaryKeyDefault='id',$uniqTextKeyDefault='uniq',array $primaryKeys=[],array $uniqTextKeys=[],$many2manyPrefix='',$tableWrapperClassDefault=false,$integerSuffixAsPolymorphism=true,$debug=DataSource::DEBUG_DEFAULT){
		$this->map = $map;
		$this->modelClassPrefix = (array)$modelClassPrefix;
		$this->entityClassDefault = $entityClassDefault;
		$this->primaryKeyDefault = $primaryKeyDefault;
		$this->uniqTextKeyDefault = $uniqTextKeyDefault;
		$this->primaryKeys = $primaryKeys;
		$this->uniqTextKeys = $uniqTextKeys;
		$this->many2manyPrefix = $many2manyPrefix;
		$this->tableWrapperClassDefault = $tableWrapperClassDefault;
		$this->integerSuffixAsPolymorphism = $integerSuffixAsPolymorphism;
		$this->debug = $debug;
		
		if(class_exists(Validate::class)){
			$this->validateService = new Validate();
		}
		
	}
	function debug($level=DataSource::DEBUG_ON){
		$this->debug = $level;
		foreach($this->mapObjects as $o)
			$o->debug($level);
	}
	function setEntityFactory($factory){
		$this->entityFactory = $factory;
	}
	function setTableWapperFactory($factory){
		$this->tableWrapperFactory = $factory;
	}
	function setModelClassPrefix($modelClassPrefix='Model\\'){
		$this->modelClassPrefix = (array)$modelClassPrefix;
	}
	function appendModelClassPrefix($modelClassPrefix){
		$this->modelClassPrefix[] = $modelClassPrefix;
	}
	function prependModelClassPrefix($modelClassPrefix){
		array_unshift($this->modelClassPrefix,$modelClassPrefix);
	}
	function setEntityClassDefault($entityClassDefault='stdClass'){
		$this->entityClassDefault = $entityClassDefault;
	}
	function setPrimaryKeyDefault($primaryKeyDefault='id'){
		$this->primaryKeyDefault = $primaryKeyDefault;
	}
	function setUniqTextKeyDefault($uniqTextKeyDefault='uniq'){
		$this->uniqTextKeyDefault = $uniqTextKeyDefault;
	}
	function offsetGet($k){
		if(!isset($this->map[$k]))
			throw new InvalidArgumentException('Try to access undefined DataSource layer "'.$k.'"');
		if(!isset($this->mapObjects[$k])){
			$this->mapObjects[$k] = $this->loadDataSource($this->map[$k]);
			if($this->debug){
				$this->mapObjects[$k]->debug($this->debug);
			}
		}
		return $this->mapObjects[$k];
	}
	function offsetSet($k,$v){
		$this->map[$k] = (array)$v;
		$this->mapObjects[$k] = null;
	}
	function offsetExists($k){
		return isset($this->map[$k]);
	}
	function offsetUnset($k){
		if(isset($this->map[$k]))
			unset($this->map[$k]);
		if(isset($this->mapObjects[$k]))
			unset($this->mapObjects[$k]);
	}
	function selectDatabase($key,$dsn,$user=null,$password=null,$config=[]){
		$this[$key] = [
			'dsn'=>$dsn,
			'user'=>$user,
			'password'=>$password,
		]+$config;
		return $this[$key];
	}
	private function loadDataSource(array $config){
		$modelClassPrefix = $this->modelClassPrefix;
		$entityClassDefault = $this->entityClassDefault;
		$primaryKey = $this->primaryKeyDefault;
		$uniqTextKey = $this->uniqTextKeyDefault;
		$primaryKeys = $this->primaryKeys;
		$uniqTextKeys = $this->uniqTextKeys;
		$many2manyPrefix = $this->many2manyPrefix;
		$tableWrapperClassDefault = $this->tableWrapperClassDefault;
		$integerSuffixAsPolymorphism = $this->integerSuffixAsPolymorphism;
		$debug = $this->debug;
		
		if(isset($config['type'])){
			$type = $config['type'];
		}
		elseif((isset($config[0])&&($dsn=$config[0]))||(isset($config['dsn'])&&($dsn=$config['dsn']))){
			$type = strtolower(substr($dsn,0,strpos($dsn,':')));
			$config['type'] = $type;
		}
		else{
			throw new InvalidArgumentException('Undefined type of DataSource, please use atleast key type, dsn or offset 0');
		}
		
		if(isset($config['modelClassPrefix'])){
			$modelClassPrefix = $config['modelClassPrefix'];
			unset($config['modelClassPrefix']);
		}
		if(isset($config['entityClassDefault'])){
			$entityClassDefault = $config['entityClassDefault'];
			unset($config['entityClassDefault']);
		}
		if(isset($config['tableWrapperClassDefault'])){
			$tableWrapperClassDefault = $config['tableWrapperClassDefault'];
			unset($config['tableWrapperClassDefault']);
		}
		if(isset($config['primaryKey'])){
			$primaryKey = $config['primaryKey'];
			unset($config['primaryKey']);
		}
		if(isset($config['uniqTextKey'])){
			$uniqTextKey = $config['uniqTextKey'];
			unset($config['uniqTextKey']);
		}
		if(isset($config['primaryKeys'])){
			$primaryKeys = $config['primaryKeys'];
			unset($config['primaryKeys']);
		}
		if(isset($config['uniqTextKeys'])){
			$uniqTextKeys = $config['uniqTextKeys'];
			unset($config['uniqTextKeys']);
		}
		if(isset($config['many2manyPrefix'])){
			$many2manyPrefix = $config['many2manyPrefix'];
			unset($config['many2manyPrefix']);
		}
		if(isset($config['integerSuffixAsPolymorphism'])){
			$integerSuffixAsPolymorphism = $config['integerSuffixAsPolymorphism'];
			unset($config['integerSuffixAsPolymorphism']);
		}
		if(isset($config['debug'])){
			$debug = $config['debug'];
			unset($config['debug']);
		}
		
		$class = __NAMESPACE__.'\\DataSource\\'.ucfirst($type);
		$dataSource = new $class($this,$type,$modelClassPrefix,$entityClassDefault,$primaryKey,$uniqTextKey,$primaryKeys,$uniqTextKeys,$many2manyPrefix,$tableWrapperClassDefault,$debug,$config,$integerSuffixAsPolymorphism);
		if($this->entityFactory){
			$dataSource->setEntityFactory($this->entityFactory);
		}
		if($this->tableWrapperFactory){
			$dataSource->setTableWapperFactory($this->tableWrapperFactory);
		}
		return $dataSource;
	}
	function getValidateService(){
		return $this->validateService;
	}
}