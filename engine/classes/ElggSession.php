<?php
/**
 * Magic session class.
 * This class is intended to extend the $_SESSION magic variable by providing an API hook
 * to plug in other values.
 *
 * Primarily this is intended to provide a way of supplying "logged in user" details without touching the session
 * (which can cause problems when accessed server side).
 *
 * If a value is present in the session then that value is returned, otherwise a plugin hook 'session:get', '$var' is called,
 * where $var is the variable being requested.
 *
 * Setting values will store variables in the session in the normal way.
 *
 * LIMITATIONS: You can not access multidimensional arrays
 *
 * This is EXPERIMENTAL.
 */
class ElggSession implements ArrayAccess {
	/** Local cache of trigger retrieved variables */
	private static $__localcache;

	function __isset($key) {
		return $this->offsetExists($key);
	}

	/** Set a value, go straight to session. */
	function offsetSet($key, $value) {
		$_SESSION[$key] = $value;
	}

	/**
	 * Get a variable from either the session, or if its not in the session attempt to get it from
	 * an api call.
	 */
	function offsetGet($key) {
		if (!ElggSession::$__localcache) {
			ElggSession::$__localcache = array();
		}

		if (isset($_SESSION[$key])) {
			return $_SESSION[$key];
		}

		if (isset(ElggSession::$__localcache[$key])) {
			return ElggSession::$__localcache[$key];
		}

		$value = NULL;
		$value = trigger_plugin_hook('session:get', $key, NULL, $value);

		ElggSession::$__localcache[$key] = $value;

		return ElggSession::$__localcache[$key];
	}

	/**
	* Unset a value from the cache and the session.
	*/
	function offsetUnset($key) {
		unset(ElggSession::$__localcache[$key]);
		unset($_SESSION[$key]);
	}

	/**
	* Return whether the value is set in either the session or the cache.
	*/
	function offsetExists($offset) {
		if (isset(ElggSession::$__localcache[$offset])) {
			return true;
		}

		if (isset($_SESSION[$offset])) {
			return true;
		}

		if ($this->offsetGet($offset)){
			return true;
		}
	}


	// Alias functions
	function get($key) {
		return $this->offsetGet($key);
	}

	function set($key, $value) {
		return $this->offsetSet($key, $value);
	}

	function del($key) {
		return $this->offsetUnset($key);
	}
}