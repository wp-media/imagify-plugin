<?php

namespace Imagify\CLI;

interface CommandInterface {
	/**
     * Get the command name.
     *
     * @return string
     */
	public function get_name();

	 /**
     * Executes the command.
     *
     * @param array $arguments Positional argument.
     * @param array $options Optional arguments.
     */
	public function __invoke( $arguments, $options );

	/**
     * Get the positional and associative arguments a command accepts.
     *
     * @return array
     */
	public function get_synopsis();

	/**
     * Get the command description.
     *
     * @return string
     */
	public function get_description();
}
