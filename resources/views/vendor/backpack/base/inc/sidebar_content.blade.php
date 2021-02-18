<!-- This file is used to store sidebar items, starting with Backpack\Base 0.9.0 -->
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('album') }}'><i class='nav-icon la la-th-large'></i> Albums</a></li>
<li class='nav-item'><a class='nav-link' href='{{ backpack_url('question') }}'><i class='nav-icon la la-question'></i> Câu hỏi</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('crawl-id') }}'><i class='nav-icon la la-cloud-download'></i> Crawl by id</a></li>
<li class='nav-item'><a class='nav-link' href='{{ backpack_url('upload-data') }}'><i class='nav-icon la la-cloud-upload'></i> Upload data</a></li>

<li class='nav-item'><a class='nav-link' href='{{ backpack_url('log') }}'><i class='nav-icon la la-terminal'></i> Logs</a></li>
<li class='nav-item'><a class='nav-link' href='{{ backpack_url('setting') }}'><i class='nav-icon la la-cog'></i> <span>Settings</span></a></li>
