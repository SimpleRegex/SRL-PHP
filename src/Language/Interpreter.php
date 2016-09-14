<?php

namespace SRL\Language;

use SRL\Builder;
use SRL\Builder\NonCapture;
use SRL\Exceptions\InterpreterException;
use SRL\Exceptions\SyntaxException;
use SRL\Interfaces\Method;
use SRL\Interfaces\TestMethodProvider;
use SRL\Language\Helpers\Cache;
use SRL\Language\Helpers\Literally;
use SRL\Language\Helpers\Matcher;
use SRL\Language\Helpers\ParenthesesParser;

/**
 * Interpreter for string commands in SRL style.
 */
class Interpreter extends TestMethodProvider
{
    /** @var string The raw SRL query. */
    protected $rawQuery;

    /** @var string[] The resolved but not executed SRL query. */
    protected $resolvedQuery = [];

    /** @var Matcher */
    protected $matcher;

    /** @var Builder The resolved and executed SRL query. */
    protected $builder;

    public function __construct(string $query)
    {
        $this->rawQuery = rtrim(trim($query), ';');
        $this->matcher = Matcher::getInstance();

        // Search for the SRL query in the local cache before building it.
        if (Cache::has($this->rawQuery)) {
            $this->builder = Cache::get($this->rawQuery);
        } else {
            $this->build();
        }
    }

    /**
     * Resolve and then build the query.
     */
    public function build()
    {
        $this->resolve();

        $this->builder = static::buildQuery($this->resolvedQuery);

        // Add built query to cache, to avoid rebuilding the same query over and over.
        Cache::add($this->rawQuery, $this->builder);
    }

    /**
     * Resolve the string array using the ParenthesesParser.
     */
    protected function resolve()
    {
        $this->resolvedQuery = $this->resolveQuery((new ParenthesesParser($this->rawQuery))->parse());
    }

    /**
     * Resolve the query array recursively and insert Methods.
     *
     * @param array $query
     * @throws InterpreterException
     * @return array
     */
    protected function resolveQuery(array $query) : array
    {
        // Using for, since the array will be altered. Foreach would change behaviour.
        for ($i = 0; $i < count($query); $i++) {
            if (is_string($query[$i])) {
                // Remove commas and remove item if empty.
                $query[$i] = str_replace(',', ' ', $query[$i]);
                if (empty($query[$i])) {
                    array_splice($query, $i, 0);
                    continue;
                }

                try {
                    // A string can be interpreted as a method. Let's try resolving the method then.
                    $method = $this->matcher->match($query[$i]);

                    // If anything was left over (for example parameters), grab them and insert them.
                    $leftOver = preg_replace("/{$method->getOriginal()}/i", '', $query[$i], 1);
                    $query[$i] = $method;
                    if (!empty($leftOver)) {
                        array_splice($query, $i + 1, 0, trim($leftOver));
                    }
                } catch (SyntaxException $e) {
                    // There could be some parameters, so we'll split them and try to parse them again
                    $split = preg_split('/[\s]+/', $query[$i], 2);
                    $query[$i] = trim($split[0]);
                    if (isset($split[1])) {
                        array_splice($query, $i + 1, 0, trim($split[1]));
                    }
                }
            } elseif (is_array($query[$i])) {
                // Nested query found. Resolve it as well.
                $query[$i] = $this->resolveQuery($query[$i]);
            } elseif (!$query[$i] instanceof Literally) {
                throw new InterpreterException('Unexpected statement: ' . json_encode($query[$i]));
            }
        }

        return $query;
    }

    /**
     * After the query was resolved, it can be built and thus executed.
     *
     * @param array $query
     * @param Builder|null $builder If no Builder is given, the default Builder will be taken.
     * @throws SyntaxException
     * @return Builder
     */
    public static function buildQuery(array $query, Builder $builder = null) : Builder
    {
        $builder = $builder ?: new Builder;

        for ($i = 0; $i < count($query); $i++) {
            $method = $query[$i];

            if (is_array($method)) {
                // User supplied parentheses. Let's execute them as a non capture group
                $builder->and(static::buildQuery($method, new NonCapture));
                continue;
            }

            if (!$method instanceof Method) {
                // At this point, there should only be methods left, since all parameters are already taken care of.
                // If that's not the case, something didn't work out.
                throw new SyntaxException("Unexpected statement: $method");
            }

            $parameters = [];
            // If there are parameters, walk through them and apply them if they don't start a new method.
            while (isset($query[$i + 1]) && !($query[$i + 1] instanceof Method)) {
                $parameters[] = $query[$i + 1];

                // Since the parameters will be appended to the method object, they are already parsed and can be
                // removed from further parsing. Don't use unset to keep keys incrementing.
                array_splice($query, $i + 1, 1);
            }

            try {
                // Now, append that method to the builder object.
                $method->setParameters($parameters)->callMethodOn($builder);
            } catch (SyntaxException $e) {
                if (is_array($parameters[0])) {
                    // An array should be treated as a non capture group. First, execute the parent method call
                    // without parameters, then add the group.
                    $method->callMethodOn($builder);
                    $builder->and(static::buildQuery($parameters[0], new NonCapture));
                } else {
                    throw new SyntaxException("Invalid parameter given for `{$method->getOriginal()}`.", 0, $e);
                }
            }
        }

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $delimiter = '/', bool $ignoreInvalid = false) : string
    {
        return $this->builder->get($delimiter, $ignoreInvalid);
    }

    /**
     * Get the underlying builder object.
     *
     * @return Builder
     */
    public function getBuilder() : Builder
    {
        return $this->builder;
    }

    /**
     * Return the raw SRL query.
     *
     * @return string
     */
    public function getRawQuery() : string
    {
        return $this->rawQuery;
    }
}
