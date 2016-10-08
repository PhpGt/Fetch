<?php
namespace phpgt\fetch;

/**
 * The Headers interface of the Fetch API allows you to perform various actions
 * on HTTP request and response headers. These actions include retrieving,
 * setting, adding to, and removing. A Headers object has an associated header
 * list, which is initially empty and consists of zero or more name and
 * value pairs.
 */
class Headers {

public function __construct(array $init = []) {

}

/**
 * Appends a new value onto an existing header inside a Headers object, or
 * adds the header if it does not already exist.
 */
public function append(string $name, string $value) {

}

/**
 * Deletes a header from the current Headers object. Throws a HeaderException
 * if the provided name does not exist.
 */
public function delete(string $name) {

}

/**
 * Returns the first value of a given header from within a Headers object. If
 * the requested header doesn't exist in the Headers object, the call
 * returns null.
 */
public function get(string $name) {

}

/**
 * Returns an array of all the values of a header within a Headers object with
 * a given name. If the requested header doesn't exist in the Headers object,
 * it returns an empty array.
 */
public function getAll():array {

}

/**
 * Returns a boolean stating whether a Headers object contains a certain header.
 */
public function has(string $name):bool {

}

/**
 * Sets a new value for an existing header inside a Headers object, or adds the
 * header if it does not already exist.
 *
 * The difference between set() and append() is that if the specified header
 * already exists and accepts multiple values, set() overwrites the existing
 * value with the new one, whereas append() appends the new value to the end of
 * the set of values.
 */
public function set(string $name, string $value) {

}

}#