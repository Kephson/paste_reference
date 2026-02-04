export class BroadcastMessage {
  constructor(channel, eventName, payload) {
    this.channel = channel;
    this.eventName = eventName;
    this.payload = payload;
  }
}