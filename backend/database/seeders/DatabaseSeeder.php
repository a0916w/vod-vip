<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 创建测试用户
        User::create([
            'nickname' => '管理员',
            'email' => 'admin@example.com',
            'password' => 'password',
            'vip_level' => 1,
            'vip_expired_at' => now()->addYear(),
        ]);

        User::create([
            'nickname' => '普通用户',
            'email' => 'user@example.com',
            'password' => 'password',
            'vip_level' => 0,
            'vip_expired_at' => null,
        ]);

        // 创建分类
        $categories = [
            ['name' => '电影', 'slug' => 'movies', 'sort_order' => 1],
            ['name' => '电视剧', 'slug' => 'tv-series', 'sort_order' => 2],
            ['name' => '综艺', 'slug' => 'variety', 'sort_order' => 3],
            ['name' => '纪录片', 'slug' => 'documentary', 'sort_order' => 4],
            ['name' => '动漫', 'slug' => 'anime', 'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        // 创建演示视频
        $sampleVideos = [
            ['title' => '星际穿越', 'cat' => 1, 'vip' => 1, 'desc' => '一部关于时空旅行的科幻史诗', 'duration' => 10140],
            ['title' => '盗梦空间', 'cat' => 1, 'vip' => 1, 'desc' => '在梦境中植入意念的惊险故事', 'duration' => 8880],
            ['title' => '肖申克的救赎', 'cat' => 1, 'vip' => 0, 'desc' => '希望让人自由', 'duration' => 8520],
            ['title' => '阿甘正传', 'cat' => 1, 'vip' => 0, 'desc' => '生活就像一盒巧克力', 'duration' => 8520],
            ['title' => '权力的游戏 S1E1', 'cat' => 2, 'vip' => 1, 'desc' => '维斯特洛大陆的史诗故事', 'duration' => 3600],
            ['title' => '绝命毒师 S1E1', 'cat' => 2, 'vip' => 1, 'desc' => '化学老师的另一面', 'duration' => 3480],
            ['title' => '老友记 S1E1', 'cat' => 2, 'vip' => 0, 'desc' => '六个朋友在纽约的故事', 'duration' => 1320],
            ['title' => '奔跑吧兄弟 EP1', 'cat' => 3, 'vip' => 0, 'desc' => '户外竞技真人秀', 'duration' => 5400],
            ['title' => '中国好声音 EP1', 'cat' => 3, 'vip' => 1, 'desc' => '音乐选秀节目', 'duration' => 5400],
            ['title' => '地球脉动', 'cat' => 4, 'vip' => 1, 'desc' => 'BBC 自然纪录片', 'duration' => 3000],
            ['title' => '舌尖上的中国', 'cat' => 4, 'vip' => 0, 'desc' => '中国美食纪录片', 'duration' => 3000],
            ['title' => '进击的巨人 EP1', 'cat' => 5, 'vip' => 1, 'desc' => '人类与巨人的战争', 'duration' => 1440],
            ['title' => '海贼王 EP1', 'cat' => 5, 'vip' => 0, 'desc' => '路飞的冒险旅程', 'duration' => 1440],
            ['title' => '鬼灭之刃 EP1', 'cat' => 5, 'vip' => 1, 'desc' => '炭治郎的复仇之路', 'duration' => 1440],
            ['title' => '你的名字', 'cat' => 5, 'vip' => 1, 'desc' => '新海诚动画电影', 'duration' => 6360],
            ['title' => '千与千寻', 'cat' => 5, 'vip' => 0, 'desc' => '宫崎骏经典之作', 'duration' => 7500],
        ];

        foreach ($sampleVideos as $i => $v) {
            Video::create([
                'title' => $v['title'],
                'cover_url' => "https://picsum.photos/seed/video{$i}/400/225",
                'video_url' => "https://sample-videos.com/video321/mp4/720/big_buck_bunny_720p_1mb.mp4",
                'preview_url' => "https://sample-videos.com/video321/mp4/240/big_buck_bunny_240p_1mb.mp4",
                'is_vip' => $v['vip'],
                'category_id' => $v['cat'],
                'description' => $v['desc'],
                'duration' => $v['duration'],
                'view_count' => rand(100, 99999),
            ]);
        }
    }
}
