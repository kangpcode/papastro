/**
 * ------------------------------------------------------------------
 *  Papastro Framework v3.0.0 — Pasway Client SDK
 *  Realtime WebSocket client (Echo & Pusher compatible)
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

(function (global) {
    'use strict';

    class PaswayChannel {
        constructor(name, client) {
            this.name = name;
            this.client = client;
            this.listeners = {};
            this.subscribed = false;
        }

        listen(event, callback) {
            if (!this.listeners[event]) {
                this.listeners[event] = [];
            }
            this.listeners[event].push(callback);
            return this;
        }

        stopListening(event, callback) {
            if (!event) {
                this.listeners = {};
            } else if (!callback) {
                delete this.listeners[event];
            } else if (this.listeners[event]) {
                this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
            }
            return this;
        }

        whisper(event, data) {
            this.client.send({
                event: 'client-' + event,
                channel: this.name,
                data: data
            });
            return this;
        }

        _dispatch(event, data) {
            if (this.listeners[event]) {
                this.listeners[event].forEach(cb => {
                    try { cb(data); } catch (e) { console.error('[Pasway] Listener error:', e); }
                });
            }
        }
    }

    class PaswayPresenceChannel extends PaswayChannel {
        constructor(name, client) {
            super(name, client);
            this.members = {};
            this._hereCallback = null;
            this._joiningCallback = null;
            this._leavingCallback = null;

            this.listen('pusher_internal:member_added', (data) => {
                const member = data.user_info || data;
                const id = data.user_id;
                this.members[id] = member;
                if (this._joiningCallback) this._joiningCallback(member);
            });

            this.listen('pusher_internal:member_removed', (data) => {
                const id = data.user_id;
                const member = this.members[id];
                delete this.members[id];
                if (this._leavingCallback) this._leavingCallback(member || { id });
            });
        }

        here(callback) {
            this._hereCallback = callback;
            if (Object.keys(this.members).length > 0) {
                callback(Object.values(this.members));
            }
            return this;
        }

        joining(callback) {
            this._joiningCallback = callback;
            return this;
        }

        leaving(callback) {
            this._leavingCallback = callback;
            return this;
        }

        _setMembers(presenceData) {
            this.members = presenceData.hash || {};
            if (this._hereCallback) {
                this._hereCallback(Object.values(this.members));
            }
        }
    }

    class Pasway {
        constructor(options = {}) {
            this.options = Object.assign({
                host: (typeof window !== 'undefined' && window.location.hostname) || '127.0.0.1',
                port: 8080,
                key: 'papastro-realtime-key',
                authEndpoint: '/pasway/auth',
                authHeaders: {},
                encrypted: false,
                autoConnect: true,
            }, options);

            this.channels = {};
            this.socket = null;
            this.socketId = null;
            this.reconnectAttempts = 0;
            this.pingTimer = null;

            if (this.options.autoConnect) {
                this.connect();
            }
        }

        connect() {
            if (this.socket && (this.socket.readyState === WebSocket.OPEN || this.socket.readyState === WebSocket.CONNECTING)) {
                return;
            }

            const protocol = this.options.encrypted || (typeof window !== 'undefined' && window.location.protocol === 'https:') ? 'wss' : 'ws';
            const url = `${protocol}://${this.options.host}:${this.options.port}/app/${this.options.key}?protocol=7&client=js&version=3.0.0`;

            try {
                this.socket = new WebSocket(url);
            } catch (e) {
                console.error('[Pasway] WebSocket connection error:', e);
                this.reconnect();
                return;
            }

            this.socket.onopen = () => {
                this.reconnectAttempts = 0;
                this.startHeartbeat();
            };

            this.socket.onclose = () => {
                this.stopHeartbeat();
                this.reconnect();
            };

            this.socket.onerror = (error) => {
                console.error('[Pasway] Socket error:', error);
            };

            this.socket.onmessage = (event) => {
                this.handleMessage(event.data);
            };
        }

        reconnect() {
            const delay = Math.min(1000 * Math.pow(1.5, this.reconnectAttempts++), 10000);
            setTimeout(() => this.connect(), delay);
        }

        startHeartbeat() {
            this.stopHeartbeat();
            this.pingTimer = setInterval(() => {
                this.send({ event: 'pusher:ping', data: {} });
            }, 30000);
        }

        stopHeartbeat() {
            if (this.pingTimer) {
                clearInterval(this.pingTimer);
                this.pingTimer = null;
            }
        }

        send(payload) {
            if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                this.socket.send(JSON.stringify(payload));
            }
        }

        handleMessage(rawData) {
            try {
                const message = JSON.parse(rawData);
                const event = message.event;
                let data = message.data;
                if (typeof data === 'string') {
                    try { data = JSON.parse(data); } catch (_) {}
                }

                if (event === 'pusher:connection_established') {
                    this.socketId = data.socket_id;
                    this.resubscribeAll();
                    return;
                }

                if (event === 'pusher:pong') {
                    return;
                }

                const channelName = message.channel;
                if (channelName && this.channels[channelName]) {
                    const channel = this.channels[channelName];

                    if (event === 'pusher_internal:subscription_succeeded') {
                        channel.subscribed = true;
                        if (channel instanceof PaswayPresenceChannel && data.presence) {
                            channel._setMembers(data.presence);
                        }
                        return;
                    }

                    channel._dispatch(event, data);
                }
            } catch (err) {
                console.error('[Pasway] Message handling failed:', err);
            }
        }

        channel(name) {
            if (!this.channels[name]) {
                this.channels[name] = new PaswayChannel(name, this);
                this.subscribe(name);
            }
            return this.channels[name];
        }

        private(name) {
            const channelName = name.startsWith('private-') ? name : 'private-' . concat(name);
            if (!this.channels[channelName]) {
                this.channels[channelName] = new PaswayChannel(channelName, this);
                this.subscribe(channelName);
            }
            return this.channels[channelName];
        }

        presence(name) {
            const channelName = name.startsWith('presence-') ? name : 'presence-' . concat(name);
            if (!this.channels[channelName]) {
                this.channels[channelName] = new PaswayPresenceChannel(channelName, this);
                this.subscribe(channelName);
            }
            return this.channels[channelName];
        }

        subscribe(channelName) {
            if (!this.socketId) {
                // Wait until connection is established
                return;
            }

            if (channelName.startsWith('private-') || channelName.startsWith('presence-')) {
                this.authenticate(channelName, (err, authData) => {
                    if (err) {
                        console.error('[Pasway] Subscription auth failed for ' + channelName, err);
                        return;
                    }
                    this.send({
                        event: 'pusher:subscribe',
                        data: Object.assign({ channel: channelName }, authData)
                    });
                });
            } else {
                this.send({
                    event: 'pusher:subscribe',
                    data: { channel: channelName }
                });
            }
        }

        authenticate(channelName, callback) {
            const body = new URLSearchParams({
                socket_id: this.socketId,
                channel_name: channelName
            });

            fetch(this.options.authEndpoint, {
                method: 'POST',
                headers: Object.assign({
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json'
                }, this.options.authHeaders),
                body: body.toString()
            })
            .then(res => {
                if (!res.ok) throw new Error(`Auth failed with status ${res.status}`);
                return res.json();
            })
            .then(data => callback(null, data))
            .catch(err => callback(err));
        }

        resubscribeAll() {
            Object.keys(this.channels).forEach(name => {
                this.subscribe(name);
            });
        }

        disconnect() {
            this.stopHeartbeat();
            if (this.socket) {
                this.socket.close();
                this.socket = null;
            }
        }
    }

    // Export to Window or Module
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = Pasway;
    } else {
        global.Pasway = Pasway;
    }

})(typeof window !== 'undefined' ? window : globalThis);
