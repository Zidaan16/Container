<?php
namespace Tiaras;
use \Closure;

/**
 * @author Zidaan16 <ahmadzidan1316@gmail.com>
 */
class Container
{
	/**
	 * @var array Semua service yang telah didefinisikan
	 */
	private $service = [];

	/**
	 *	@var array Singleton instance
	 */
	private $instance = [];

	/**
	 * Definisikan service ke container
	 * 
	 * @param string $name Berikan sebuah nama untuk service yang akan di daftarkan
	 * @param string|closure $resolver Object yang akan dieksekusi jika sebuah service dipanggil
	 * @param bool $singleton Dengan singleton
	 * 
	 * @return void
	 */
	public function bind(String $name, String|Closure $resolver, Bool $singleton = false): void
	{

		if (!empty($this->service[$name])) throw new \Exception("Service $name already exists.");
		if ($singleton && $resolver instanceof Closure) throw new \Exception("Singleton only work with class.");
		
		$this->service[$name] = ['resolver' => $resolver, 'singleton' => $singleton];
	}

	/**
	 * Definisikan service dengan pola singleton, sehingga instance tidak dibuat berkali-kali
	 * 
	 * @param string $name Berikan sebuah nama untuk service yang akan di daftarkan
	 * @param object|string $resolver Object atau class yang akan dieksekusi jika sebuah service dipanggil
	 * 
	 * @return void
	 */
	public function singleton(String $name, String $resolver): void
	{
		$this->bind($name, $resolver, true);
	}

	/**
	 * Mengembalikkan object dengan memenuhi dependency yang dibutuhkan
	 * Atau mengembalikan object yang telah didefinisikan
	 * @param string|\Closure $class classname
	 * 
	 * @return \Closure
	 */
	public function make(String $name): Closure
	{
		if (!empty($this->service[$name])) {
			if ($this->service[$name]['resolver'] instanceof Closure) {
				return $this->service[$name]['resolver']($this);
			}
		}

		$reflector = new \ReflectionClass($name);
		$dependency = $this->dependencyResolver($reflector->getConstructor());
		$obj = $reflector->newInstanceArgs($dependency);

		return $obj;
	}

	/**
	 * Memenuhi parameter yang dibutuhkan pada container 
	 * 
	 * @param \ReflectionMethod $constructor Constructor
	 * 
	 * @return array
	 */
	protected function dependencyResolver(\ReflectionMethod $constructor): array
	{
		$params = [];
		foreach ($constructor->getParameters() as $value) {
			$paramType = $value->getType()->getName();
			$name = $value->getName();

			if (empty($this->service[$paramType])) throw new \Exception("Service $paramType not exists.");

			$resolver = $this->service[$paramType]['resolver'];
			$singleton = $this->service[$paramType]['singleton'];

			if ($singleton) {
				if (empty($this->instance[$name])) {
					$this->instance[$name] = new $resolver();
				}
				$params[$name] = $this->instance[$name];
			} else {
				$params[$name] = new $resolver();
			}
		}

		return $params;
	}

}
