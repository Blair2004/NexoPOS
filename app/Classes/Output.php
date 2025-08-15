<?php

namespace App\Classes;

use Illuminate\Support\Facades\View;

class Output
{
    protected $output = [];

    /**
     * Adds a view to the output array.
     *
     * @param mixed $view The view to add to the output.
     */
    public function addOutput( $view )
    {
        $this->output[] = $view;
    }

    /**
     * Creates a view instance and adds it to the output array.
     *
     * @param  string $view    The name of the view.
     * @param  array  $options Optional parameters to pass to the view.
     * @return $this  Returns the current instance for method chaining.
     */
    public function addView( $view, $options = [] )
    {
        $this->output[] = View::make( $view, $options );

        return $this;
    }

    /**
     * Clears all output from the output array.
     */
    public function clear()
    {
        $this->output = [];
    }

    /**
     * Sets the output array to contain only the specified view.
     *
     * @param mixed $view The view to set as the sole output.
     */
    public function setOutput( $view )
    {
        $this->output = [ $view ];
    }

    /**
     * Converts the output array to a string by concatenating all views.
     *
     * @return string The concatenated string representation of the output.
     */
    public function __toString()
    {
        return collect( $this->output )
            ->map( fn( $output ) => (string) $output )
            ->join( '' );
    }

    /**
     * Dispatches a class and forwards additional arguments to its dispatch method.
     *
     * @param  string $class The class name to dispatch.
     * @return Output The output instance containing the result of the dispatch.
     */
    public static function dispatch( string $class ): Output
    {
        // let's take other arguments of this method on an array
        $args = func_get_args();
        // remove the first argument which is the class name
        array_shift( $args );

        $output = new self;

        // We'll make sure to forward the arguments to the class
        $class::dispatch( $output, ...$args );

        return $output;
    }
}
