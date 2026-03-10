<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();

    const uid = Math.random().toString(36).slice(2, 8);
    const g = (n: number) => `${uid}-g${n}`;
    const gUrl = (n: number) => `url(#${g(n)})`;
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 200 244"
    fill="none"
    class={className}
    {...rest}
>
  <path d="m141.9 46.94c17.94-6.15 24.18-23.27 22.77-39.03l-0.25-1.24-1.05-0.14c-19.33-1.48-33.33 6.68-38.53 25.21-2.59-9.06-7.76-16.4-13.27-21.55l-10.29 9.73 0.18 0.14c8.42 6.03 16.28 14.8 17.86 26.13-38.18 0.89-76.86 25.46-76.86 55.24 0 8.45 5.12 13.83 12 15.56v44.9c0 25.56 14.84 48.09 38.44 61.26 8.35 4.61 16.47 6.23 31.74 20.85 10.54-11.37 19.29-14.24 30.45-20.02 23.33-11.12 39.37-34.11 39.37-62.09v-44.5c8.38-2.42 12.32-8.58 12.32-16.28-0.73-18.27-21.63-47.89-64.88-54.17z" fill={gUrl(0)}/>
  <path d="m61.01 117.6v44.26c0 23.9 14.61 44.88 35.15 56.14 3.09 1.66 5.88 2.96 9.76 4.4-11.26-14.6-21.15-31.24-21.15-55.32v-49.48h-23.76z" fill={gUrl(1)}/>
  <path d="m164.8 117.6v49.48c0 20.54-8.17 37.47-21.04 54.62 3.32-1.7 6.47-3.15 9.26-4.6 19.77-10.68 34.71-30.89 34.71-55.24v-44.26h-22.93z" fill={gUrl(2)}/>
  <path d="m91.71 117.6v49.48c0 23.86 12.6 42.82 33.21 66.28 17.8-19.99 33.21-41.31 33.21-66.28v-49.48h-66.42z" fill={gUrl(3)}/>
  <path d="m124.9 201.6c18.89 0 32.33-17.5 32.95-27.2v-47.32h-66.16v40c0 17.57 14.32 34.52 33.21 34.52z" fill={gUrl(4)}/>
  <path d="m61.01 127.1h23.76v-7.08h-23.76v7.08z" fill={gUrl(5)}/>
  <path d="m164.8 127.1h22.93v-7.08h-22.93v7.08z" fill={gUrl(6)}/>
  <path d="m71.31 127.1v34.78c0 21.22 10.41 38.37 26.41 48.94-8.61-13.94-12.95-26.5-12.95-43.72v-40h-13.46z" fill={gUrl(7)}/>
  <path d="m177.1 127.1v34.78c0 18.85-9.37 36.09-23.78 46.5 8.16-13.02 11.53-24.88 11.53-41.28v-40h12.25z" fill={gUrl(8)}/>
  <path d="m51.91 88.81c-2.14 4.08-3.5 8.41-3.25 12.98 0.65 5.38 4.35 8.62 10.16 8.62h5.6c0.79-8.62-4.5-18.3-12.51-21.6z" fill={gUrl(9)}/>
  <path d="m76.11 79.87c-5.05 6.24-8.5 13.54-9.45 18.21-2.43-4.87-5.95-9.44-11.18-12.37 2.63-4.42 4.93-7.63 8.23-11l12.4 5.16z" fill={gUrl(10)}/>
  <path d="m89.11 64.11c-4.12 3.08-7.7 7.04-10.93 11.3l-9.89-4.79c4.54-3.71 8.42-6.57 12.42-8.16l8.4 1.65z" fill={gUrl(11)}/>
  <path d="m100.1 54.71c-3.3 2.14-6.09 4.56-8.72 6.98l-4.78-2.42c4.42-1.99 8.83-3.59 13.5-4.56z" fill={gUrl(12)}/>
  <path d="m119.7 62.82c-4.9 3.37-8.6 6.79-10.53 9.72-3.7-3.58-8.06-6.35-12.84-8.78 4.12-4.05 8.39-7.28 11.69-8.65l11.68 7.71z" fill={gUrl(13)}/>
  <path d="m134.8 51.71-10.13 7.14-8.97-7.42c6.12-0.82 12.72-0.85 19.1 0.28z" fill={gUrl(14)}/>
  <path d="m162.7 58.96-4.87 2.42-8.16-7.14c4.6 1.24 9.02 2.78 13.03 4.72z" fill={gUrl(15)}/>
  <path d="m182.1 69.91-11 5.16c-3.46-4.45-6.55-7.88-9.52-10.31l7.15-2.42c4.87 2.31 9.47 4.91 13.37 7.57z" fill={gUrl(16)}/>
  <path d="m193.7 85.31c-5.42 3.13-8.97 6.8-12.07 12.77-2.43-6.37-5.4-12.37-8.85-17.07l13.46-6.3c3.19 3.06 5.85 6.81 7.46 10.6z" fill={gUrl(17)}/>
  <path d="m196.8 89.21c-6.72 4-11.36 11.65-11.36 21.2h6.34c6.15 0 9.02-5.1 9.02-9.8 0-4.4-1.67-8.33-4-11.4z" fill={gUrl(18)}/>
  <path d="m80.61 83.43c-6.66 8.45-9.95 17.48-10.33 26.98h23.63c1-10.3-5.6-20.44-13.3-26.98z" fill={gUrl(19)}/>
  <path d="m95.72 68.11c-5.51 4-9.66 9.6-12.09 12.58 6.72 5.97 10.84 12.52 12.09 16.94 2.59-7.66 6.47-14.02 9.89-19.12-2.93-3.44-6.7-7.29-9.89-10.4z" fill={gUrl(20)}/>
  <path d="m124.7 66.22c-7.15 5.89-10.96 10.52-14.15 14.47 5.96 7.14 11.48 14.34 13.91 19.42 2.93-9 7.57-15 11.98-20-2.63-4-7.3-9.49-11.74-13.89z" fill={gUrl(21)}/>
  <path d="m140.1 55.11 12.6 8.65c-5.01 2.92-9 5.85-12.6 8.78-3.7-4.05-7.61-7.47-11.21-9.89l11.21-7.54z" fill={gUrl(22)}/>
  <path d="m157 67.25c4.3 3.68 7.84 8.16 9.77 11.68-6.47 5.96-10.59 11.93-13.53 18.7-2.93-8.45-6.39-14.42-9.84-19.12 4.3-4.42 8.71-8.32 13.6-11.26z" fill={gUrl(23)}/>
  <path d="m169.8 83.43c6.66 7.43 9.09 16.22 9.47 26.98h-24.2c-0.27-10.3 4.89-20.44 14.73-26.98z" fill={gUrl(24)}/>
  <path d="m110.5 82.61c-8.34 10.6-11.06 19.18-11.06 27.8h22.36c0.46-9.93-4.92-20.66-11.3-27.8z" fill={gUrl(25)}/>
  <path d="m138.9 82.61c7.98 10.28 10.83 19.18 10.83 27.8h-22.37c0-9.93 4.9-20.66 11.54-27.8z" fill={gUrl(26)}/>
  <path d="m139 16.21 6.15-3.42v12.07l-6.28 6.88 0.13-15.53z" fill="#C99743"/>
  <path d="m148 11.91 11-1.65-11 11v-9.35z" fill="#C99743"/>
  <path d="m160.6 13.61-0.85 9.45h-10.44l11.29-9.45z" fill="#C99743"/>
  <path d="m148 25.71h9.46l-3.46 7.15h-13.46l7.46-7.15z" fill="#C99743"/>
  <path d="m134 37.11 6.11 2-8.69 4 2.58-6z" fill="#C99743"/>
  <path d="m133.2 34.31-5.81 6c0-7.14 2.43-14.4 5.81-19.05v13.05z" fill="#B7662A"/>
  <path d="m178 76.51 6.28-1.8c4.47 3.2 7.52 7.38 9.44 10.6l-6.66 5.1c-1.83-5.1-5.16-10.48-9.06-13.9z" fill="#FEFFFE" opacity=".4"/>
  <defs>
    <linearGradient id={g(0)} x1="124.7" x2="124.7" y1="6.201" y2="244" gradientUnits="userSpaceOnUse">
      <stop stop-color="#5F3124" offset="0"/><stop stop-color="#350B24" offset="1"/>
    </linearGradient>
    <linearGradient id={g(1)} x1="83.46" x2="83.46" y1="117.6" y2="222.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#A88049" offset="0"/><stop stop-color="#6E3534" offset="1"/>
    </linearGradient>
    <linearGradient id={g(2)} x1="165.8" x2="165.8" y1="117.6" y2="221.7" gradientUnits="userSpaceOnUse">
      <stop stop-color="#D38634" offset="0"/><stop stop-color="#782822" offset="1"/>
    </linearGradient>
    <linearGradient id={g(3)} x1="124.9" x2="124.9" y1="117.6" y2="233.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#D17A2B" offset="0"/><stop stop-color="#8F2A22" offset="1"/>
    </linearGradient>
    <linearGradient id={g(4)} x1="124.8" x2="124.8" y1="127.1" y2="201.6" gradientUnits="userSpaceOnUse">
      <stop stop-color="#BA4726" offset="0"/><stop stop-color="#9A391F" offset="1"/>
    </linearGradient>
    <linearGradient id={g(5)} x1="72.89" x2="72.89" y1="120" y2="127.1" gradientUnits="userSpaceOnUse">
      <stop stop-color="#743227" offset="0"/><stop stop-color="#6B3528" offset="1"/>
    </linearGradient>
    <linearGradient id={g(6)} x1="176.3" x2="176.3" y1="120" y2="127.1" gradientUnits="userSpaceOnUse">
      <stop stop-color="#B67438" offset="0"/><stop stop-color="#DFAF52" offset="1"/>
    </linearGradient>
    <linearGradient id={g(7)} x1="84.51" x2="84.51" y1="127.1" y2="210.8" gradientUnits="userSpaceOnUse">
      <stop stop-color="#CBB263" offset="0"/><stop stop-color="#9B7047" offset="1"/>
    </linearGradient>
    <linearGradient id={g(8)} x1="165.2" x2="165.2" y1="127.1" y2="208.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#F9DFA4" offset="0"/><stop stop-color="#C19E63" offset="1"/>
    </linearGradient>
    <linearGradient id={g(9)} x1="56.74" x2="56.74" y1="88.81" y2="110.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#662228" offset="0"/><stop stop-color="#542428" offset="1"/>
    </linearGradient>
    <linearGradient id={g(10)} x1="65.8" x2="65.8" y1="74.71" y2="98.08" gradientUnits="userSpaceOnUse">
      <stop stop-color="#974925" offset="0"/><stop stop-color="#783328" offset="1"/>
    </linearGradient>
    <linearGradient id={g(11)} x1="78.7" x2="78.7" y1="62.46" y2="75.41" gradientUnits="userSpaceOnUse">
      <stop stop-color="#B87539" offset="0"/><stop stop-color="#A2612E" offset="1"/>
    </linearGradient>
    <linearGradient id={g(12)} x1="93.36" x2="93.36" y1="54.71" y2="61.69" gradientUnits="userSpaceOnUse">
      <stop stop-color="#9E5828" offset="0"/><stop stop-color="#8F5028" offset="1"/>
    </linearGradient>
    <linearGradient id={g(13)} x1="108" x2="108" y1="55.11" y2="72.54" gradientUnits="userSpaceOnUse">
      <stop stop-color="#C39240" offset="0"/><stop stop-color="#9F5228" offset="1"/>
    </linearGradient>
    <linearGradient id={g(14)} x1="125.3" x2="125.3" y1="50.73" y2="58.85" gradientUnits="userSpaceOnUse">
      <stop stop-color="#D7B072" offset="0"/><stop stop-color="#B37637" offset="1"/>
    </linearGradient>
    <linearGradient id={g(15)} x1="156.2" x2="156.2" y1="54.24" y2="61.38" gradientUnits="userSpaceOnUse">
      <stop stop-color="#B97939" offset="0"/><stop stop-color="#A2602E" offset="1"/>
    </linearGradient>
    <linearGradient id={g(16)} x1="171.8" x2="171.8" y1="62.34" y2="75.07" gradientUnits="userSpaceOnUse">
      <stop stop-color="#F0CF81" offset="0"/><stop stop-color="#D4943E" offset="1"/>
    </linearGradient>
    <linearGradient id={g(17)} x1="183.2" x2="183.2" y1="74.71" y2="98.08" gradientUnits="userSpaceOnUse">
      <stop stop-color="#F1D084" offset="0"/><stop stop-color="#D59540" offset="1"/>
    </linearGradient>
    <linearGradient id={g(18)} x1="193.1" x2="193.1" y1="89.21" y2="110.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#D98126" offset="0"/><stop stop-color="#B54E26" offset="1"/>
    </linearGradient>
    <linearGradient id={g(19)} x1="82.1" x2="82.1" y1="83.43" y2="110.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#5E2628" offset="0"/><stop stop-color="#682728" offset="1"/>
    </linearGradient>
    <linearGradient id={g(20)} x1="94.62" x2="94.62" y1="68.11" y2="97.63" gradientUnits="userSpaceOnUse">
      <stop stop-color="#C98435" offset="0"/><stop stop-color="#9E3A26" offset="1"/>
    </linearGradient>
    <linearGradient id={g(21)} x1="123.5" x2="123.5" y1="66.22" y2="100.1" gradientUnits="userSpaceOnUse">
      <stop stop-color="#F6B958" offset="0"/><stop stop-color="#D68430" offset="1"/>
    </linearGradient>
    <linearGradient id={g(22)} x1="140.8" x2="140.8" y1="55.11" y2="72.54" gradientUnits="userSpaceOnUse">
      <stop stop-color="#F6B958" offset="0"/><stop stop-color="#D68430" offset="1"/>
    </linearGradient>
    <linearGradient id={g(23)} x1="155.1" x2="155.1" y1="67.25" y2="97.63" gradientUnits="userSpaceOnUse">
      <stop stop-color="#F0993B" offset="0"/><stop stop-color="#B94E26" offset="1"/>
    </linearGradient>
    <linearGradient id={g(24)} x1="167.2" x2="167.2" y1="83.43" y2="110.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#D46A28" offset="0"/><stop stop-color="#9E3A26" offset="1"/>
    </linearGradient>
    <linearGradient id={g(25)} x1="110.6" x2="110.6" y1="82.61" y2="110.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#D96126" offset="0"/><stop stop-color="#B54D26" offset="1"/>
    </linearGradient>
    <linearGradient id={g(26)} x1="138.5" x2="138.5" y1="82.61" y2="110.4" gradientUnits="userSpaceOnUse">
      <stop stop-color="#A94026" offset="0"/><stop stop-color="#D76D2B" offset="1"/>
    </linearGradient>
  </defs>
</svg>
