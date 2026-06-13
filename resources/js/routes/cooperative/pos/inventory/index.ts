import receipts from './receipts'
import transfers from './transfers'
import counts from './counts'

const inventory = {
    receipts: Object.assign(receipts, receipts),
    transfers: Object.assign(transfers, transfers),
    counts: Object.assign(counts, counts),
}

export default inventory