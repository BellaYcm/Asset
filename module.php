<?php
namespace AssetManager;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Zend\EventManager\EventInterface;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
//通过事件，触发onBootstrap,传过来肯定是interface接口,
    public function onBootstrap(EventInterface $e)
    {


        // 得到所有的已加载模块的名字   getApplication()获取应用
        $serviceManager = $e->getApplication()->getServiceManager();

        /* @var \Zend\ModuleManager\ModuleManager $moduleManager */

        //ModuleManager为$serviceManager里的一个服务
        $moduleManager = $serviceManager->get('ModuleManager');
        //得到所有加载的模块，把键值取出
//        var_dump($moduleManager->getLoadedModules());exit;
        $loadedModulesName = array_keys($moduleManager->getLoadedModules());


        $moduleFolderPath = realpath(__DIR__ . '/../');
//        echo($moduleFolderPath);exit;

        $moveToFolderPath = realpath($moduleFolderPath . '../../public');

        foreach ($loadedModulesName as $moduleName) {

            $moduleAssetFolderPath = $moduleFolderPath . '/' . $moduleName . '/asset';
//            echo($moduleAssetFolderPath."<br>");
            //得到各个模块下的asset路径,迭代asset下的所有东西

            if (!is_dir($moduleAssetFolderPath)) {
                //不存在 跳过
                continue;
            }
            //$directoryIterator只是个迭代器，没办法展开，放到$iteratorIterator里，$iteratorIterator迭代递归的东西,RecursiveDirectoryIterator::SKIP_DOTS:跳过..
            $directoryIterator = new RecursiveDirectoryIterator($moduleAssetFolderPath, RecursiveDirectoryIterator::SKIP_DOTS);
            //  RecursiveIteratorIterator::SELF_FIRST先迭代父元素
            $iteratorIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

            /* @var \SplFileInfo $splFileInfo */
            foreach ($iteratorIterator as $splFileInfo) {
                //echo( $splFileInfo->getPathname()."<br>".$moveToFolderPath."<br>".$moduleAssetFolderPath."<hr>");

                $moveToFolderFullPath = str_replace($moduleAssetFolderPath, $moveToFolderPath, $splFileInfo->getPathname());

                if ($splFileInfo->isDir()) {
                    if (!is_dir($moveToFolderFullPath)) {
                        mkdir($moveToFolderFullPath);
                    }
                }else {
                    copy($splFileInfo->getPathname(), $moveToFolderFullPath);
                }

            }
        }

        // merge 在别的config里配置
        //通过$serviceManager取配置
        $config = $serviceManager->get('Config');



        if(isset($config['assetManager']) && isset($config['assetManager']['merge'])){
            $merge = $config['assetManager']['merge'];
//    var_dump($merge);exit;array(1) { ["pro/ad/global.js"]=> array(2) { [0]=> string(37) "vendor/bootstrap3/js/bootstrap.min.js" [1]=> string(34) "vendor/jquery/jquery-1.11.1.min.js" } }

            foreach($merge as $newFilePath=>$needMergeFiles){

                $mergeContent = '';
                foreach($needMergeFiles as $needMergeFile){

                    if(is_file($moveToFolderPath . '/' . $needMergeFile)){

                        $mergeContent .= file_get_contents($moveToFolderPath . '/' . $needMergeFile);
                    }
                }
//                var_dump($newFilePath);exit;string(20) "pro/ad/global.js"
                //把新地址写成数组
                $newFilePaths = explode('/',$newFilePath);
                //推出最后一个，保留的是文件夹名
                array_pop($newFilePaths);

                if(!is_dir($moveToFolderPath . '/' .implode('/',$newFilePaths))){
                    mkdir($moveToFolderPath . '/' .implode('/',$newFilePaths),0777,true);
                }
                file_put_contents($moveToFolderPath . '/' . $newFilePath,$mergeContent);
            }
            echo(success);

        }







    }
}
