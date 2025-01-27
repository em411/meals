export class MercureSubscribeHandler {

    private eventSource: EventSource;

    constructor(topics: Array<string>, onRecieve: Function) {
        
        let url = new URL('https://meals.test:8081/.well-known/mercure');
        topics.forEach(topic => {
            url.searchParams.append('topic', topic);
        });

        this.eventSource  = new EventSource(url);
        this.eventSource.onmessage = ({data}) => onRecieve(JSON.parse(data));
    }
}
