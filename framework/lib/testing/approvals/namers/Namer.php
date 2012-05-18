<?php

/**
 *
 */
interface Namer
{
    /**
       * @param string $extensionWithoutDot
       * @return string
       */
      public function getApprovedFile($extensionWithoutDot);

      /**
       * @param string $extensionWithoutDot
       * @return string
       */
      public function getReceivedFile($extensionWithoutDot);


      /**
       * @return string
       */
      public function getCallingTestClassName();

      /**
       * @return string
       */
      public function getCallingTestMethodName();

      /**
       * @return string
       */
      public function getCallingTestDirectory();
}