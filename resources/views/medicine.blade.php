<x-layouts.app :title="__('Medicine')">
    <style>
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

        select.form-control,
        select.form-control option {
            background-color: #343a40;
            /* Bootstrap bg-dark */
            color: #fff;
        }
    </style>

    <div class="container mt-4">
        <h1 class="mb-4 text-center">藥品資料查詢</h1>
        <form action="" method="GET" class="mb-4 p-4 rounded shadow">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="license_number">許可號</label>
                    <input type="text" name="license_number" id="license_number" class="form-control" placeholder="許可號"
                        value="{{ request('license_number') }}">
                </div>
                <div class="form-group col-md-3">
                    <label for="chinese_name">中文名稱</label>
                    <input type="text" name="chinese_name" id="chinese_name" class="form-control" placeholder="名稱"
                        value="{{ request('chinese_name') }}">
                </div>
                <div class="form-group col-md-3">
                    <label for="english_name">英文名稱</label>
                    <input type="text" name="english_name" id="english_name" class="form-control" placeholder="英文"
                        value="{{ request('english_name') }}">
                </div>
            </div>
            <h2 class="mt-4">特徵</h2>
            <div class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label for="attr_key">特徵類型</label>
                    <select name="attr_key" id="attr_key" class="form-control bg-dark text-white">
                        <option value="">空</option>
                        @foreach ($resource['appearance'] as $key => $value)
                            <option value="{{ $value }}" {{ request('attr_key') == $value ? 'selected' : '' }}>
                                {{ $key }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="attr_value">特徵敘述</label>
                    <input type="text" name="attr_value" id="attr_value" class="form-control" placeholder="特徵敘述"
                        value="{{ request('attr_value') }}">
                </div>
                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-primary w-100">查詢</button>
                </div>
            </div>
        </form>

        <nav aria-label="Page navigation" class="mb-4">
            <ul class="pagination justify-content-center">
                {{ $medicines->appends(request()->query())->links() }}
            </ul>
        </nav>

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
</x-layouts.app>
