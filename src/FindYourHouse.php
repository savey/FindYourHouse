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


class FindYourHouse
{

    private $workflow;


    public static $allSearchRegionsMap = [
        'shushan' => '蜀山',
        'luyang'  => '庐阳',
        'zhengwu' => '政务',
        'gaoxin8' => '高新',
        'zhengwu' => '政务'
        //...other
    ];

    public static $searchUrls = [
        'find_house'  => '',
        'find_region' => 'https://hf.lianjia.com/xiaoqu/%s/%s'
    ];


    public function __construct()
    {
        $this->workflow = new Workflow();
    }

    public function findByHouseName($houseName)
    {

    }

    public function findByRegion($region)
    {
        $key       = array_search($region, self::$allSearchRegionsMap);

        $document  = QueryList::getInstance()->get(sprintf(self::$searchUrls['find_region'], $key, ''));

        $html      = $document->rules([
                'title'  => ['.xiaoquListItem .title a', 'text'],
                'link'   => ['.xiaoquListItem .title a', 'href'],
                'area'   => ['.positionInfo', 'text'],
                'price'  => ['.totalPrice span', 'text']
            ])
            ->query()->getData();


        if ($html) {
            foreach ($html as $item) {

                $area = sprintf('区域/%s/单价/%s', str_replace(array("\r\n", "\r", "\n", " "), "", $item['area']), $item['price']);
                $this->workflow->result()
                    ->uid($item['price'])
                    ->title($item['title'])
                    ->autocomplete($item['title'])
                    ->subtitle($area)
                    ->arg($item['title'])
                    ->quicklookurl($item['link'])
                    ->icon('icon-list.png')
                    ->valid(true);
            }
        }
        return $this->workflow->output();
    }
}