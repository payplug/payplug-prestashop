<?php

namespace PayPlug\tests\mock;

use Mockery;

class MockHelper extends Mockery
{
    public static function createSetCacheMock($cacheMock, &$arrayCache)
    {
        $cacheMock->shouldReceive('setCache')
            ->andReturnUsing(function ($cache_id, $to_cache) use (&$arrayCache) {
                $arrayCache[$cache_id] = $to_cache;

                return $arrayCache;
            })
        ;
    }

    public static function createAddLogMock($loggerMock, &$arrayLog)
    {
        $loggerMock->shouldReceive('addLog')
            ->andReturnUsing(function ($message, $level = 'info') use (&$arrayLog) {
                $arrayLog[] = ['level' => $level, 'message' => $message];

                return $arrayLog;
            })
        ;
    }

    public static function createMockFactory($classPathname)
    {
        $mock = \Mockery::mock('alias:' . $classPathname);
        $mock->shouldReceive('factory')
            ->andReturnSelf()
        ;

        return $mock;
    }

    public static function createToolsMock($classPathname)
    {
        $tools = self::createMockFactory($classPathname);
        $tools->shouldReceive('tool')
            ->andReturnUsing(function ($action, $value, $params2 = false) {
                switch ($action) {
                    case 'strlen':
                        return strlen($value);

                    case 'strpos':
                        return strpos($value, $params2);

                    case 'strtoupper':
                        return strtoupper($value);

                    case 'strtolower':
                        return strtolower($value);

                    case 'displayPrice':
                        $value = number_format($value, 2) . ' €';

                        return str_replace('.', ',', $value);

                    case 'ps_round':
                        return round($value, $params2);

                    case 'file_get_contents':
                        return file_get_contents('php://input');

                    default:
                        throw new Mockery\Exception('ERROR : Missing method "' . $action . '" in createToolsMock (MockHelper.php)');

                        break;
                }

                return false;
            })
        ;
        $tools->shouldReceive('substr')
            ->andReturnUsing(function ($param, $param1, $param2) {
                return substr($param, $param1, $param2);
            })
        ;

        return $tools;
    }

    public static function createTranslateMock($classPathname)
    {
        $translate = self::createMockFactory($classPathname);
        $translate->shouldReceive('translate')
            ->andReturnUsing(function ($module_class, $string, $repository_name) {
                return $string;
            })
        ;

        return $translate;
    }

    public static function createValidateMock($classPathname)
    {
        $validate = self::createMockFactory($classPathname);
        $validate->shouldReceive('validate')
            ->andReturnUsing(function ($action, $object) {
                if ('' === $object || is_null($object)) {
                    return false;
                }
                if ('isLoadedObject' == $action && !is_object($object)) {
                    return false;
                }

                return true;
            })
        ;

        return $validate;
    }

    public static function createContextMock($classPathname)
    {
        return \Mockery::mock($classPathname);
//        $context
//            ->shouldReceive('get')
//            ->andReturn(ContextMock::get());
    }

    public static function createSetParamstMock($classPathname)
    {
        $context = \Mockery::mock($classPathname);
        $context->shouldReceive('setParams')
            ->andReturnSelf()
        ;

        return $context;
    }

    public static function createAddressMock($classPathname)
    {
        $address = self::createMockFactory($classPathname);
        $address->shouldReceive('get')
            ->andReturn(AddressMock::get())
        ;

        return $address;
    }

    public static function createAssignMock($classPathname)
    {
        $assign = self::createMockFactory($classPathname);
        $assign->shouldReceive('assign')
            ->andReturn(true)
        ;

        return $assign;
    }

    public static function createQueryMock($classPathname)
    {
        $query = self::createMockFactory($classPathname);
        $query->shouldReceive('escape')
            ->andReturnUsing(function ($string, $htmlOk = false) {
                return $string;
            })
        ;

        return $query;
    }
}
