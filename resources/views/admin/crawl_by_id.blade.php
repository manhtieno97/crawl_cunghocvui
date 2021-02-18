@extends(backpack_view('blank'))

@php

$sites = \App\Models\Question::getSite();


@endphp

@section('header')
    <div class="container-fluid">
        <div class="title mb-2">
            <h2>
                <span class="text-capitalize">Crawl site</span>
                <small id="datatable_info_stack"></small>
            </h2>
        </div>
        <div >
            <div class="d-flex">
                <form id="form-crawl-id"  class="form w-100" method="POST" action="{{ backpack_url('crawl-id') }}" >
                    @csrf
                    <div class="mb-2 row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="row">
                                <div class="col-md-4 col-sm-4 col-xs-12">
                                    <label class="mb-0">Chọn site <i>(*)</i></label>
                                    <select name="site"  class="form-control selectpicker" data-live-search="true" required>
                                        <option value="">-- Chọn site --</option>
                                        @if(!empty($sites ))
                                            @foreach($sites as $key => $site)
                                                <option value="{{ $key }}" data-item="{{ $key }}">{{ $site }}</option>
                                            @endforeach
                                        @endif

                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    <label class="mb-0">Nhập giới hạn dưới id </label>
                                    <input class="form-control mr-2" type="number" name="id_from" id="">
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-12">
                                    <label class="mb-0">Nhập giới hạn trên id</label>
                                    <input class="form-control mr-2" type="number" name="id_to" id="">
                                </div>
                                <div class="col-md-2 col-sm-4 col-xs-12 mt-4">
                                    <a href="javascript:void(0)" onclick="crawl()" class="btn btn-primary w-100 mr-2">Crawl
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('content')
@endsection

@section('after_styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <style>
        #form-crawl-id{
            padding: 10px;
            background: #fff;
        }
        .title{
            background: #fff;
            padding: 5px;
        }
    </style>
@endsection

@section('after_scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"  ></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        function crawl() {
            var site = $('select[name="site"]').find("option:selected").attr('data-item');
            var title,text,classButton,icon;
            if(site == '' || typeof site === 'undefined'){
                title = "Lỗi";
                text = 'Bạn chưa chọn site !';
                classButton = "d-none";
                icon = 'warning';
            }else {
                title = "Bạn có chắc crawl ?";
                text = "Site : " + site;
                classButton = "bg-success";
                icon = 'success';
            }
            swal({
                title: title,
                text:  text,
                icon: icon,
                buttons: {
                    cancel: {
                        text: "Cancel",
                        value: null,
                        visible: true,
                        className: "bg-secondary",
                        closeModal: true,
                    },
                    create: {
                        text: "Crawl",
                        value: true,
                        visible: true,
                        className: classButton,
                    }
                },
            }).then((value) => {
                if (value) {
                    $('#form-crawl-id').submit();
                }
            });
        };




    </script>
@endsection
