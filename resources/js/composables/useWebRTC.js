import { ref, onUnmounted } from 'vue';
import axios from 'axios';

export function useWebRTC(authUserId) {
    const localStream  = ref(null);
    const remoteStream = ref(null);
    const callStatus   = ref('idle'); // idle | ringing | active | ended
    const isMuted      = ref(false);
    const isVideoOff   = ref(false);

    let peerConnection = null;
    let currentCallUuid = null;

    const iceServers = {
        iceServers: [
            { urls: import.meta.env.VITE_STUN_SERVER || 'stun:stun.l.google.com:19302' },
            ...(import.meta.env.VITE_TURN_SERVER ? [{
                urls:       import.meta.env.VITE_TURN_SERVER,
                username:   import.meta.env.VITE_TURN_USERNAME,
                credential: import.meta.env.VITE_TURN_CREDENTIAL,
            }] : []),
        ],
    };

    async function startCall(conversationId, type = 'video') {
        try {
            localStream.value = await navigator.mediaDevices.getUserMedia({
                video: type === 'video',
                audio: true,
            });

            peerConnection = new RTCPeerConnection(iceServers);
            localStream.value.getTracks().forEach(t => peerConnection.addTrack(t, localStream.value));

            peerConnection.ontrack = (e) => { remoteStream.value = e.streams[0]; };
            peerConnection.onicecandidate = (e) => {
                if (e.candidate && currentCallUuid) {
                    axios.post(`/calls/${currentCallUuid}/signal`, {
                        signal: JSON.stringify({ type: 'candidate', candidate: e.candidate }),
                    });
                }
            };

            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            const res = await axios.post(`/conversations/${conversationId}/call`, {
                type,
                signal: JSON.stringify({ type: 'offer', sdp: offer }),
            });

            currentCallUuid = res.data.call_uuid;
            callStatus.value = 'ringing';

        } catch (err) {
            console.error('Erreur démarrage appel:', err);
            endCall();
        }
    }

    async function answerCall(callUuid, offerSignal) {
        try {
            currentCallUuid = callUuid;
            const offer = JSON.parse(offerSignal);

            localStream.value = await navigator.mediaDevices.getUserMedia({
                video: offer.sdp?.includes('m=video'),
                audio: true,
            });

            peerConnection = new RTCPeerConnection(iceServers);
            localStream.value.getTracks().forEach(t => peerConnection.addTrack(t, localStream.value));
            peerConnection.ontrack = (e) => { remoteStream.value = e.streams[0]; };
            peerConnection.onicecandidate = (e) => {
                if (e.candidate) {
                    axios.post(`/calls/${currentCallUuid}/signal`, {
                        signal: JSON.stringify({ type: 'candidate', candidate: e.candidate }),
                    });
                }
            };

            await peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            await axios.post(`/calls/${callUuid}/signal`, {
                status: 'active',
                signal: JSON.stringify({ type: 'answer', sdp: answer }),
            });

            callStatus.value = 'active';

        } catch (err) {
            console.error('Erreur réponse appel:', err);
            declineCall(callUuid);
        }
    }

    async function handleSignal(signal) {
        if (!peerConnection) return;
        const data = JSON.parse(signal);

        if (data.type === 'answer') {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(data));
            callStatus.value = 'active';
        } else if (data.type === 'candidate') {
            await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
        }
    }

    async function declineCall(callUuid) {
        await axios.post(`/calls/${callUuid}/signal`, {
            status: 'declined',
            signal: JSON.stringify({ type: 'declined' }),
        });
        callStatus.value = 'ended';
    }

    async function endCall() {
        try {
            if (currentCallUuid) {
                await axios.post(`/calls/${currentCallUuid}/signal`, {
                    status: 'ended',
                    signal: JSON.stringify({ type: 'ended' }),
                }).catch(() => {}); // Ignorer les erreurs réseau
            }
        } catch (err) {
            console.error('Erreur fermeture appel:', err);
        } finally {
            peerConnection?.close();
            peerConnection = null;
            localStream.value?.getTracks().forEach(t => t.stop());
            localStream.value = null;
            remoteStream.value = null;
            callStatus.value = 'ended';
            currentCallUuid = null;
        }
    }
   
    function toggleMute() {
        isMuted.value = !isMuted.value;
        localStream.value?.getAudioTracks().forEach(t => { t.enabled = !isMuted.value; });
    }

    function toggleVideo() {
        isVideoOff.value = !isVideoOff.value;
        localStream.value?.getVideoTracks().forEach(t => { t.enabled = !isVideoOff.value; });
    }

    onUnmounted(endCall);

    return {
        localStream, remoteStream, callStatus, isMuted, isVideoOff,
        startCall, answerCall, handleSignal, declineCall, endCall,
        toggleMute, toggleVideo,
    };
}
