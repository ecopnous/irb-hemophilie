<div class="p-4">
    <flux:skeleton.group animate="shimmer">
        <flux:table>
            <flux:table.rows>
                @foreach (range(1, 5) as $order)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:skeleton.line />
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:skeleton.line />
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:skeleton.line />
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:skeleton.line />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:skeleton.group>
</div>
