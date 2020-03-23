<?php
/**
 * project:  Findhouse from hf.lianjia.com
 * Date:     2019/8/13
 * User:     Savey
 * Desc: workflow Properties @see https://www.alfredapp.com/help/workflows/inputs/script-filter/json/
 */

namespace Savey\FindYourHouse;


use Alfred\Workflows\Workflow;
use QL\QueryList;
use Swoole\Process;
use Swoole\Runtime;
use Co;


class FindYourHouse
{

    private $workflow;

    private $path;
    private $fileName;

    public static $allSearchRegionsMap = [
        "蜀山" => "https://hf.lianjia.com/xiaoqu/shushan/",
        "庐阳"  => "https://hf.lianjia.com/xiaoqu/luyang/",
        "政务" => "https://hf.lianjia.com/xiaoqu/zhengwu/",
        "经开"  => "https://hf.lianjia.com/xiaoqu/jingkai2/",
        "高薪" => "https://hf.lianjia.com/xiaoqu/gaoxin8/"
    ];



    public function __construct()
    {
        $this->workflow = new Workflow();
        $this->path = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
    }

    //查看是否生成当天的数据文件
    private function loadDataIntoFile($regionArg)
    {

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777);
        }
        Runtime::enableCoroutine();
        foreach (self::$allSearchRegionsMap as $regoin => $url) {
           go(function() use ($regoin, $url) {
                //获取数据
                $name     = date('Ymd') . $regoin;
                $fileName = $this->path . $name . '.json';
                if (!file_exists($fileName)) {
                    touch($fileName);
                    $document = QueryList::get($url);
                    $html     = $document->rules([
                        'title' => ['.xiaoquListItem .title a', 'text'],
                        'link'  => ['.xiaoquListItem .title a', 'href'],
                        'area'  => ['.positionInfo', 'text'],
                        'price' => ['.totalPrice span', 'text']
                    ])
                        ->query()->getData();
                    file_put_contents($fileName, json_encode($html, JSON_UNESCAPED_UNICODE));

                    echo $regoin . PHP_EOL;
                }
            });
        }

        while (1) {
            $name     = date('Ymd') . $regionArg;
            $fileName = $this->path . $name . '.json';
            if (file_exists($fileName)) {
                return json_decode(file_get_contents($fileName), 1);
                break;
            }
        }
    }


    public function findByRegion($region)
    {
        if (array_key_exists($region, self::$allSearchRegionsMap) == false) {
            return null;
        }

        $datas = $this->loadDataIntoFile($region);
        foreach ($datas as $item) {
            $area = sprintf('区域/%s/单价/%s', str_replace(array("\r\n", "\r", "\n", " "), "", $item['area']), $item['price']);
            $this->workflow->result()
                ->uid($item['price'])
                ->title($item['title'])
                ->autocomplete($item['title'])
                ->subtitle($area)
                ->arg($item['link'])
                ->quicklookurl($item['link'])
                ->icon('icon-list.png')
                ->valid(true);
        }
        return $this->workflow->output();
    }
}