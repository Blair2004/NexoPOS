const { Vue }       =   require( '../bootstrap' );
const nsInput      =   Vue.component( 'ns-select', {
    data: () => {
        return {
        }
    },
    props: [ 'name', 'placeholder' ],
    template: `
    <div class="flex flex-col">
        <label for="price" class="block text-sm leading-5 font-medium text-gray-700">Price</label>
        <div class="mt-1 relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm sm:leading-5">
                $
                </span>
            </div>
            <select id="price" class="form-input block w-full pl-7 pr-12 sm:text-sm sm:leading-5" placeholder="0.00" />
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center">
                <select aria-label="Currency" class="form-select h-full py-0 pl-2 pr-7 border-transparent bg-transparent text-gray-500 sm:text-sm sm:leading-5">
                <option>USD</option>
                <option>CAD</option>
                <option>EUR</option>
                </select>
            </div>
        </div>
    </div>
    `,
});

module.exports     =   nsInput;