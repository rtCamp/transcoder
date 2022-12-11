

const TransCodeStatus ={
    Processing: 'Your file is getting transcoded.Please refresh after some time.',
    Processed: 'processed',
    Queue: 'This file is still in the queue. Please refresh after some time.',
    ServerReady: 'Your server should be ready to receive the transcoded file.',
    Completed: 'Your file is transcoded successfully. Please refresh the page.',
    Error: 'Unfortunately, Transcoder failed to transcode this file.'
}


module.exports = { TransCodeStatus };