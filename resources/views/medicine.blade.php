<x-layouts.app :title="__('Medicine')">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>藥品資料</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <style>
            .w-5 {
                width: 5vw;
            }

            .h-5 {
                height: 5vh;
            }

            img {
                max-width: 100%;
                width: 20vw;
                height: auto;
                display: block;
                margin: 0 auto;
            }

            label {
                font-weight: bold;
                /* 使标签加粗 */
                margin-right: 5px;
                /* 标签和文本之间的间距 */
            }

            ul {
                padding-left: 0;
                list-style-type: none;
            }

            li {
                margin: 0;
                padding: 0;
            }
        </style>
    </head>

    <body>

        <div class="container mt-4">
            <h1>藥品資料</h1>
            <form action="" method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" name="license_number" class="form-control" placeholder="許可號"
                            value="{{ request('license_number') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="chinese_name" class="form-control" placeholder="名稱"
                            value="{{ request('chinese_name') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="english_name" class="form-control" placeholder="英文"
                            value="{{ request('english_name') }}">
                    </div>

                </div>
                <h2>特徵</h2>
                <div class="row mt-2">
                    <select name="attr_key">
                        <option value="">空</option>
                        @foreach ($resource['appearance'] as $key => $value)
                            <option value="{{ $value }}" {{ request('attr_key') == $value ? 'selected' : '' }}>
                                {{ $key }}</option>
                        @endforeach
                    </select>
                    <div class="col-md-3"><input type="text" name="attr_value" class="form-control"
                            placeholder="特徵敘述" value="{{ request('attr_value') }}"></div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">查詢</button>
                    </div>
                </div>
            </form>

            <div class="pagination">
                {{ $medicines->appends(request()->query())->links() }}
            </div>

            <!-- 資料預覽 -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>基本資訊</th>
                            <th>額外資訊</th>
                            <th>特徵</th>
                            <th>圖片</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($medicines as $medicine)
                            <tr>
                                <td>
                                    <ul>
                                        <li><label>許可號:</label><span>{{ $medicine->license_number }}</span></li>
                                        <li><label>中文:</label><span>{{ $medicine->chinese_name }}</span></li>
                                        <li><label>英文:</label><span>{{ $medicine->english_name }}</span></li>
                                    </ul>
                                </td>
                                {{-- <td>
                                <ul>
                                    <li><label>狀態:</label><span>{{ $medicine->status ?? 'none' }}</span></li>
                                    <li><label>ATC_CODE:</label><span>{{ $medicine->atc_code ?? 'none' }}</span></li>
                                    <li><label>單方/複方:</label><span>{{ $medicine->single_compound ?? 'none' }}</span>
                                    </li>
                                    <li><label>劑型:</label><span>{{ $medicine->dosage_form ?? 'none' }}</span></li>
                                    <li><label>限制項目:</label><span>{{ $medicine->restrictions ?? 'none' }}</span></li>
                                    <li><label>適應症:</label><span>{{ $medicine->indications ?? 'none' }}</span></li>
                                    <li><label>藥品類別:</label><span>{{ $medicine->drug_type ?? 'none' }}</span></li>
                                </ul>
                            </td> --}}
                                <td>
                                    <ul>
                                        @foreach ($medicine->toAppearance as $item)
                                            <li><label>{{ $keyMap[$item->attr_key] ?? '' }}:</label><span>{{ $item->attr_value }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>
                                    @foreach ($medicine->toFiles as $file)
                                        <img src="{{ $file->path() }}"></img>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>


        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>

    </html>
</x-layouts.app>
