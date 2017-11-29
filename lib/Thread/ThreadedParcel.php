<?php

namespace Amp\Parallel\Thread;

use Amp\Parallel\Sync\Parcel;
use Amp\Promise;
use Amp\Success;
use Amp\Sync\ThreadedMutex;
use function Amp\call;

/**
 * A thread-safe container that shares a value between multiple threads.
 */
class ThreadedParcel implements Parcel {
    /** @var \Amp\Sync\ThreadedMutex */
    private $mutex;

    /** @var \Threaded */
    private $storage;

    /**
     * Creates a new shared object container.
     *
     * @param mixed $value The value to store in the container.
     */
    public function __construct($value) {
        $this->mutex = new ThreadedMutex;
        $this->storage = new class($value) extends \Threaded {
            /** @var mixed */
            private $value;

            /**
             * @param mixed $value
             */
            public function __construct($value) {
                $this->value = $value;
            }

            /**
             * @return mixed
             */
            public function get() {
                return $this->value;
            }

            /**
             * @param mixed $value
             */
            public function set($value) {
                $this->value = $value;
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function unwrap(): Promise {
        return new Success($this->storage->get());
    }

    /**
     * @return \Amp\Promise
     */
    public function synchronized(callable $callback): Promise {
        return call(function () use ($callback) {
            /** @var \Amp\Sync\Lock $lock */
            $lock = yield $this->mutex->acquire();

            try {
                $result = yield call($callback, $this->storage->get());

                if ($result !== null) {
                    $this->storage->set($result);
                }
            } finally {
                $lock->release();
            }

            return $result;
        });
    }
}
